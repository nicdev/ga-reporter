<?php

namespace Nicdev\GoogleAnalytics\Tests;

use DateTime;
use PHPUnit\Framework\TestCase;
use Nicdev\GoogleAnalytics\Client;
use Nicdev\GoogleAnalytics\Report;

class ReportTest extends TestCase
{
    private Report $report;

    protected function setUp(): void
    {
        $client = new Client([
            'credentials_path' => __DIR__ . '/fixtures/test-credentials.json'
        ]);
        $this->report = new Report($client);
    }

    public function testGetPageViews(): void
    {
        $startDate = new DateTime('7 days ago');
        $endDate = new DateTime();
        
        $result = $this->report->getPageViews('test_view_id', $startDate, $endDate);
        $this->assertNotNull($result);
    }

    public function testGetAudienceMetrics(): void
    {
        $startDate = new DateTime('7 days ago');
        $endDate = new DateTime();
        
        $result = $this->report->getAudienceMetrics('test_view_id', $startDate, $endDate);
        $this->assertNotNull($result);
    }

    public function testGetGA4Report(): void
    {
        $startDate = new DateTime('7 days ago');
        $endDate = new DateTime();
        
        $dimensions = ['date'];
        $metrics = ['activeUsers', 'newUsers'];
        
        $result = $this->report->getGA4Report(
            'test_property_id',
            $dimensions,
            $metrics,
            $startDate,
            $endDate
        );
        $this->assertNotNull($result);
    }
}