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
    private Config $config;

    public function __construct(array|Config $config)
    {
        $this->config = is_array($config) ? new Config($config) : $config;
        $this->initializeClient();
    }

    private function initializeClient(): void
    {
        $this->client = new GoogleClient();
        
        if ($this->config->getApplicationName()) {
            $this->client->setApplicationName($this->config->getApplicationName());
        }
        
        $this->client->setScopes($this->config->getScopes());
        
        if ($this->config->getCredentialsPath()) {
            $this->client->setAuthConfig($this->config->getCredentialsPath());
        }
        
        if ($this->config->getAccessToken()) {
            $this->client->setAccessToken($this->config->getAccessToken());
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