<?php

namespace Nicdev\GoogleAnalytics;

use Google\Client as GoogleClient;
use Google\Service\Analytics;
use Google\Service\AnalyticsData;

class Client
{
    private GoogleClient $client;
    private ?Analytics $analyticsService = null;
    private ?AnalyticsData $analyticsDataService = null;

    public function __construct(array $config = [])
    {
        $this->client = new GoogleClient();
        $this->client->setScopes([
            'https://www.googleapis.com/auth/analytics.readonly'
        ]);
        
        if (isset($config['credentials_path'])) {
            $this->client->setAuthConfig($config['credentials_path']);
        }
        
        if (isset($config['access_token'])) {
            $this->client->setAccessToken($config['access_token']);
        }
    }

    public function getAnalyticsService(): Analytics
    {
        if (!$this->analyticsService) {
            $this->analyticsService = new Analytics($this->client);
        }
        return $this->analyticsService;
    }

    public function getAnalyticsDataService(): AnalyticsData
    {
        if (!$this->analyticsDataService) {
            $this->analyticsDataService = new AnalyticsData($this->client);
        }
        return $this->analyticsDataService;
    }

    public function setAccessToken(string $accessToken): void
    {
        $this->client->setAccessToken($accessToken);
    }

    public function refreshAccessToken(): ?string
    {
        if ($this->client->isAccessTokenExpired()) {
            $this->client->fetchAccessTokenWithRefreshToken();
            return $this->client->getAccessToken();
        }
        return null;
    }
}