<?php

namespace Nicdev\GoogleAnalytics;

use DateTime;
use Exception;
use Google\Service\AnalyticsData\Filter;
use Google\Service\AnalyticsData\FilterExpression;
use Google\Service\AnalyticsData\FilterExpressionList;
use Google\Service\AnalyticsData\RunReportRequest;
use Google\Service\AnalyticsData\StringFilter;

/**
 * Reporter class handles Google Analytics 4 data retrieval and reporting operations
 * @category Analytics
 * @package Nicdev\GoogleAnalytics
 * @author Nicdev
 * @license MIT
 * @link https://github.com/nicdev/google-analytics
 */
class Reporter
{
    private Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    private function formatPropertyId(string $propertyId): string
    {
        // If the ID already starts with 'properties/', return it as is
        if (str_starts_with($propertyId, 'properties/')) {
            return $propertyId;
        }

        // Otherwise, prepend 'properties/'
        return 'properties/' . $propertyId;
    }

    public function validatePropertyId(string $propertyId): bool
    {
        $analyticsData = $this->client->getAnalyticsDataService();

        try {
            $request = new RunReportRequest;
            $request->setDateRanges([
                [
                    'startDate' => 'yesterday',
                    'endDate' => 'yesterday',
                ],
            ]);
            $request->setMetrics([['name' => 'activeUsers']]);

            $analyticsData->properties->runReport(
                $this->formatPropertyId($propertyId),
                $request
            );

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function getGA4Data(
        string $propertyId,
        ?array $metrics = ['activeUsers'],
        ?array $dimensions = ['date'],
        ?array $filterConfig = null,
        ?DateTime $startDate = null,
        ?DateTime $endDate = null
    ) {
        ray('getGA4Data');

        // Check if we need to chunk metrics due to GA4 API limit of 10 metrics per request
        if (count($metrics) > 10) {
            return $this->getGA4DataWithChunkedMetrics(
                $propertyId,
                $metrics,
                $dimensions,
                $filterConfig,
                $startDate,
                $endDate
            );
        }

        return $this->makeGA4Request(
            $propertyId,
            $metrics,
            $dimensions,
            $filterConfig,
            $startDate,
            $endDate
        );
    }

    private function getGA4DataWithChunkedMetrics(
        string $propertyId,
        array $metrics,
        array $dimensions,
        ?array $filterConfig,
        ?DateTime $startDate,
        ?DateTime $endDate
    ) {
        // Split metrics into chunks of 10
        $metricChunks = array_chunk($metrics, 10);
        $responses = [];

        ray('Metrics will be chunked into ' . count($metricChunks) . ' requests');
        ray('Metric chunks:', $metricChunks);

        // Make API calls for each chunk
        foreach ($metricChunks as $chunkIndex => $metricChunk) {
            ray('Making request for chunk ' . ($chunkIndex + 1) . ' with metrics:', $metricChunk);

            $response = $this->makeGA4Request(
                $propertyId,
                $metricChunk,
                $dimensions,
                $filterConfig,
                $startDate,
                $endDate
            );
            $responses[] = $response;
        }

        // Merge all responses
        ray('Merging ' . count($responses) . ' responses');
        return $this->mergeGA4Responses($responses);
    }

    private function makeGA4Request(
        string $propertyId,
        array $metrics,
        array $dimensions,
        ?array $filterConfig,
        ?DateTime $startDate,
        ?DateTime $endDate
    ) {
        $analyticsData = $this->client->getAnalyticsDataService();

        $startDate = $startDate ?: new DateTime('-30 days');
        $endDate = $endDate ?: new DateTime('today');

        $request = new RunReportRequest;

        $request->setDateRanges([
            [
                'startDate' => $startDate->format('Y-m-d'),
                'endDate' => $endDate->format('Y-m-d'),
            ],
        ]);

        ray($metrics);

        $request->setMetrics(array_map(function ($metric) {
            return ['name' => $metric];
        }, $metrics));

        ray($dimensions);

        $request->setDimensions(array_map(function ($dimension) {
            return ['name' => $dimension];
        }, $dimensions));

        ray($filterConfig);

        // Add filter if provided
        if ($filterConfig) {
            $filter = $this->buildFilter($filterConfig);

            if ($filter) {
                $request->setDimensionFilter($filter);
            }
        }

        ray($request);

        try {
            $report = $analyticsData->properties->runReport(
                $this->formatPropertyId($propertyId),
                $request
            );

            ray($report);

            return $report;
        } catch (Exception $e) {
            throw new Exception('GA4 API Error: ' . $e->getMessage());
        }
    }

    private function mergeGA4Responses(array $responses): object
    {
        if (empty($responses)) {
            throw new Exception('No responses to merge');
        }

        if (count($responses) === 1) {
            return $responses[0];
        }

        $baseResponse = $responses[0];
        $mergedMetricHeaders = [];
        $mergedRows = [];

        // Collect all metric headers
        foreach ($responses as $response) {
            foreach ($response->getMetricHeaders() as $header) {
                $mergedMetricHeaders[] = $header;
            }
        }

        // Get the base rows structure from the first response
        $baseRows = $baseResponse->getRows();
        if (!$baseRows) {
            return $baseResponse;
        }

        // Convert rows to arrays for easier manipulation
        $responseRowsArrays = [];
        foreach ($responses as $response) {
            $responseRowsArrays[] = iterator_to_array($response->getRows() ?: []);
        }

        // Merge metric values for each row
        $baseRowsArray = iterator_to_array($baseRows);
        foreach ($baseRowsArray as $rowIndex => $baseRow) {
            $mergedMetricValues = [];

            // Add metric values from each response
            foreach ($responseRowsArrays as $responseRows) {
                if (isset($responseRows[$rowIndex])) {
                    $responseRow = $responseRows[$rowIndex];
                    foreach ($responseRow->getMetricValues() as $metricValue) {
                        $mergedMetricValues[] = $metricValue;
                    }
                }
            }

            // Create new row with merged metric values
            $mergedRow = clone $baseRow;
            $mergedRow->setMetricValues($mergedMetricValues);
            $mergedRows[] = $mergedRow;
        }

        // Create merged response
        $mergedResponse = clone $baseResponse;
        $mergedResponse->setMetricHeaders($mergedMetricHeaders);
        $mergedResponse->setRows($mergedRows);

        return $mergedResponse;
    }

    private function buildFilter(array $filterConfig): ?FilterExpression
    {
        if (isset($filterConfig['filterType']) && $filterConfig['filterType'] === 'andGroup') {
            $filterExpression = new FilterExpression;
            $andGroup = new FilterExpressionList;

            $expressions = array_map(function ($expr) {
                $stringFilter = new StringFilter;
                $stringFilter->setValue($expr['stringFilter']['value']);
                $stringFilter->setMatchType($expr['stringFilter']['matchType']);
                $stringFilter->setCaseSensitive(
                    $expr['stringFilter']['caseSensitive'] ?? false
                );

                $filter = new Filter;
                $filter->setFieldName($expr['fieldName']);
                $filter->setStringFilter($stringFilter);

                $expression = new FilterExpression;
                $expression->setFilter($filter);

                return $expression;
            }, $filterConfig['expressions']);

            $andGroup->setExpressions($expressions);
            $filterExpression->setAndGroup($andGroup);

            return $filterExpression;
        }

        if (! isset($filterConfig['fieldName']) || ! isset($filterConfig['stringFilter'])) {
            return null;
        }

        $stringFilter = new StringFilter;
        if (isset($filterConfig['stringFilter']['value'])) {
            $stringFilter->setValue($filterConfig['stringFilter']['value']);
        }
        if (isset($filterConfig['stringFilter']['matchType'])) {
            $stringFilter->setMatchType($filterConfig['stringFilter']['matchType']);
        }
        // Add explicit case sensitivity setting here too
        $stringFilter->setCaseSensitive(
            $filterConfig['stringFilter']['caseSensitive'] ?? false
        );

        $filter = new Filter;
        $filter->setFieldName($filterConfig['fieldName']);
        $filter->setStringFilter($stringFilter);

        $filterExpression = new FilterExpression;
        $filterExpression->setFilter($filter);

        return $filterExpression;
    }

    public function formatResponse($response): array
    {
        $result = [];
        $rows = $response->getRows();

        if (! $rows) {
            return $result;
        }

        foreach ($rows as $row) {
            $rowData = [];

            // Get dimension values
            $dimensionValues = $row->getDimensionValues();
            if ($dimensionValues) {
                $dimensionValuesArray = iterator_to_array($dimensionValues);
                foreach ($response->getDimensionHeaders() as $i => $header) {
                    if (isset($dimensionValuesArray[$i])) {
                        $rowData[$header->getName()] = $dimensionValuesArray[$i]->getValue();
                    }
                }
            }

            // Get metric values
            $metricValues = $row->getMetricValues();
            if ($metricValues) {
                $metricValuesArray = iterator_to_array($metricValues);
                foreach ($response->getMetricHeaders() as $i => $header) {
                    if (isset($metricValuesArray[$i])) {
                        $rowData[$header->getName()] = $metricValuesArray[$i]->getValue();
                    }
                }
            }

            $result[] = $rowData;
        }

        return $result;
    }
}
