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
        return 'properties/'.$propertyId;
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

        $request->setMetrics(array_map(function ($metric) {
            return ['name' => $metric];
        }, $metrics));

        $request->setDimensions(array_map(function ($dimension) {
            return ['name' => $dimension];
        }, $dimensions));

        // Add filter if provided
        if ($filterConfig) {
            $filter = $this->buildFilter($filterConfig);

            if ($filter) {
                $request->setDimensionFilter($filter);
            }
        }

        try {
            $report = $analyticsData->properties->runReport(
                $this->formatPropertyId($propertyId),
                $request
            );

            return $report;
        } catch (Exception $e) {
            throw new Exception('GA4 API Error: '.$e->getMessage());
        }
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
            foreach ($response->getDimensionHeaders() as $i => $header) {
                $rowData[$header->getName()] = $dimensionValues[$i]->getValue();
            }

            // Get metric values
            $metricValues = $row->getMetricValues();
            foreach ($response->getMetricHeaders() as $i => $header) {
                $metricName = $header->getName();
                $value = $metricValues[$i]->getValue();

                // Handle different metric types
                switch ($metricName) {
                    case 'bounceRate':
                    case 'engagementRate':
                        // These are already percentages from GA4
                        $rowData[$metricName] = (float) $value;
                        break;
                    case 'active28DayUsers':
                    case 'active7DayUsers':
                    case 'active1DayUsers':
                    case 'totalUsers':
                    case 'screenPageViews':
                        // These are integers
                        $rowData[$metricName] = (int) $value;
                        break;
                    case 'averageSessionDuration':
                        // This is in seconds
                        $rowData[$metricName] = (float) $value;
                        break;
                    default:
                        // For any other metrics, keep as is
                        $rowData[$metricName] = $value;
                }
            }

            $result[] = $rowData;
        }

        return $result;
    }
}
