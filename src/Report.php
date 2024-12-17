<?php

namespace Nicdev\GoogleAnalytics;

use DateTime;
use Google\Service\Analytics\GaData;
use Google\Service\AnalyticsData\RunReportResponse;

class Report
{
    private Client $client;
    
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function getPageViews(string $viewId, DateTime $startDate, DateTime $endDate): GaData
    {
        $analytics = $this->client->getAnalyticsService();
        
        return $analytics->data_ga->get(
            'ga:' . $viewId,
            $startDate->format('Y-m-d'),
            $endDate->format('Y-m-d'),
            'ga:pageviews',
            [
                'dimensions' => 'ga:pagePath',
                'sort' => '-ga:pageviews'
            ]
        );
    }

    public function getRealTimeUsers(string $viewId): array
    {
        $analytics = $this->client->getAnalyticsService();
        
        return $analytics->data_realtime->get(
            'ga:' . $viewId,
            'rt:activeUsers',
            [
                'dimensions' => 'rt:pagePath'
            ]
        );
    }

    public function getAudienceMetrics(string $viewId, DateTime $startDate, DateTime $endDate): GaData
    {
        $analytics = $this->client->getAnalyticsService();
        
        return $analytics->data_ga->get(
            'ga:' . $viewId,
            $startDate->format('Y-m-d'),
            $endDate->format('Y-m-d'),
            'ga:users,ga:newUsers,ga:sessions,ga:bounceRate',
            [
                'dimensions' => 'ga:date'
            ]
        );
    }

    public function getEventMetrics(string $viewId, DateTime $startDate, DateTime $endDate): GaData
    {
        $analytics = $this->client->getAnalyticsService();
        
        return $analytics->data_ga->get(
            'ga:' . $viewId,
            $startDate->format('Y-m-d'),
            $endDate->format('Y-m-d'),
            'ga:totalEvents,ga:uniqueEvents',
            [
                'dimensions' => 'ga:eventCategory,ga:eventAction',
                'sort' => '-ga:totalEvents'
            ]
        );
    }

    public function getGA4Report(
        string $propertyId,
        array $dimensions,
        array $metrics,
        DateTime $startDate,
        DateTime $endDate
    ): RunReportResponse {
        $analyticsData = $this->client->getAnalyticsDataService();
        
        return $analyticsData->properties->runReport([
            'property' => 'properties/' . $propertyId,
            'dateRanges' => [
                [
                    'startDate' => $startDate->format('Y-m-d'),
                    'endDate' => $endDate->format('Y-m-d'),
                ]
            ],
            'dimensions' => array_map(function($dimension) {
                return ['name' => $dimension];
            }, $dimensions),
            'metrics' => array_map(function($metric) {
                return ['name' => $metric];
            }, $metrics)
        ]);
    }
}
