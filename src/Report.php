<?php

namespace Nicdev\GoogleAnalytics;

use DateTime;
use Exception;
use Google\Client as GoogleClient;
use Google\Service\AnalyticsData;
use Google\Service\AnalyticsData\Filter;
use Google\Service\AnalyticsData\FilterExpression;
use Google\Service\AnalyticsData\RunReportRequest;
use Google\Service\AnalyticsData\StringFilter;

class Report
{
    private Client $client;
    
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function getGA4Data(
        string $propertyId,
        DateTime $startDate,
        DateTime $endDate,
        array $metrics = ['activeUsers'],
        array $dimensions = ['date'],
        ?array $filterConfig = null
    ) {
        $analyticsData = $this->client->getAnalyticsDataService();
        
        $request = new RunReportRequest();
        
        $request->setDateRanges([
            [
                'startDate' => $startDate->format('Y-m-d'),
                'endDate' => $endDate->format('Y-m-d'),
            ]
        ]);
        
        $request->setMetrics(array_map(function($metric) {
            return ['name' => $metric];
        }, $metrics));
        
        $request->setDimensions(array_map(function($dimension) {
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
            return $analyticsData->properties->runReport(
                'properties/' . $propertyId,
                $request
            );
        } catch (Exception $e) {
            throw new Exception('GA4 API Error: ' . $e->getMessage());
        }
    }

    private function buildFilter(array $filterConfig): ?FilterExpression
    {
        if (!isset($filterConfig['fieldName']) || !isset($filterConfig['stringFilter'])) {
            return null;
        }

        $stringFilter = new StringFilter();
        if (isset($filterConfig['stringFilter']['value'])) {
            $stringFilter->setValue($filterConfig['stringFilter']['value']);
        }
        if (isset($filterConfig['stringFilter']['matchType'])) {
            $stringFilter->setMatchType($filterConfig['stringFilter']['matchType']);
        }

        $filter = new Filter();
        $filter->setFieldName($filterConfig['fieldName']);
        $filter->setStringFilter($stringFilter);

        $filterExpression = new FilterExpression();
        $filterExpression->setFilter($filter);

        return $filterExpression;
    }

    public function formatResponse($response): array
    {
        $result = [];
        $rows = $response->getRows();
        
        if (!$rows) {
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
                $rowData[$header->getName()] = $metricValues[$i]->getValue();
            }
            
            $result[] = $rowData;
        }

        return $result;
    }
}