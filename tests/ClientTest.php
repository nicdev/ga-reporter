<?php

namespace NicDev\GoogleAnalytics\Tests;

use Google\Service\Analytics;
use Google\Service\AnalyticsData;
use Nicdev\GoogleAnalytics\Client;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{
    private Client $client;

    protected function setUp(): void
    {
        $this->client = new Client([
            'credentials_path' => __DIR__.'/fixtures/test-credentials.json',
        ]);
    }

    public function test_get_analytics_service(): void
    {
        $service = $this->client->getAnalyticsService();
        $this->assertInstanceOf(Analytics::class, $service);
    }

    public function test_get_analytics_data_service(): void
    {
        $service = $this->client->getAnalyticsDataService();
        $this->assertInstanceOf(AnalyticsData::class, $service);
    }

    public function test_set_access_token(): void
    {
        $token = json_encode(['access_token' => 'test_token']);
        $this->client->setAccessToken($token);
        $this->assertNotNull($this->client->refreshAccessToken());
    }
}
