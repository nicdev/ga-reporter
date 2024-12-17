<?php

namespace NicDev\GoogleAnalytics\Tests;

use Google\Service\Analytics;
use Google\Service\AnalyticsData;
use PHPUnit\Framework\TestCase;
use Nicdev\GoogleAnalytics\Client;

class ClientTest extends TestCase
{
    private Client $client;

    protected function setUp(): void
    {
        $this->client = new Client([
            'credentials_path' => __DIR__ . '/fixtures/test-credentials.json'
        ]);
    }

    public function testGetAnalyticsService(): void
    {
        $service = $this->client->getAnalyticsService();
        $this->assertInstanceOf(Analytics::class, $service);
    }

    public function testGetAnalyticsDataService(): void
    {
        $service = $this->client->getAnalyticsDataService();
        $this->assertInstanceOf(AnalyticsData::class, $service);
    }

    public function testSetAccessToken(): void
    {
        $token = json_encode(['access_token' => 'test_token']);
        $this->client->setAccessToken($token);
        $this->assertNotNull($this->client->refreshAccessToken());
    }
}

