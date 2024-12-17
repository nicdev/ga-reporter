<?php

namespace Nicdev\GoogleAnalytics\Tests;

use DateTime;
use Nicdev\GoogleAnalytics\Client;
use Nicdev\GoogleAnalytics\Reporter;
use PHPUnit\Framework\TestCase;

class ReporterTest extends TestCase
{
    private Reporter $reporter;

    protected function setUp(): void
    {
        $client = new Client([
            'credentials_path' => __DIR__ . '/fixtures/test-credentials.json'
        ]);
        $this->reporter = new Reporter($client);
    }

    public function testGetPageViews(): void
    {
        $startDate = new DateTime('7 days ago');
        $endDate = new DateTime();
        
        $result = $this->reporter->getPageViews('test_view_id', $startDate, $endDate);
        $this->assertNotNull($result);
    }

    public function testGetAudienceMetrics(): void
    {
        $startDate = new DateTime('7 days ago');
        $endDate = new DateTime();
        
        $result = $this->reporter->getAudienceMetrics('test_view_id', $startDate, $endDate);
        $this->assertNotNull($result);
    }

    public function testGetGA4Reporter(): void
    {
        $startDate = new DateTime('7 days ago');
        $endDate = new DateTime();
        
        $dimensions = ['date'];
        $metrics = ['activeUsers', 'newUsers'];
        
        $result = $this->reporter->getGA4Reporter(
            'test_property_id',
            $dimensions,
            $metrics,
            $startDate,
            $endDate
        );
        $this->assertNotNull($result);
    }
}