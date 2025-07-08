<?php

namespace Nicdev\GoogleAnalytics;

use Google\Auth\Credentials\UserRefreshCredentials;
use Google\Client as GoogleClient;
use Google\Service\Analytics;
use Google\Service\AnalyticsData;

class Client
{
    private GoogleClient $client;

    private ?Analytics $analyticsService = null;

    private ?AnalyticsData $analyticsDataService = null;

    private Config $config;

    private ?UserRefreshCredentials $credentials = null;

    public function __construct(array|Config $config)
    {
        $this->config = is_array($config) ? new Config($config) : $config;
        $this->initializeClient();
    }

    private function initializeClient(): void
    {
        $this->client = new GoogleClient;

        if ($this->config->getApplicationName()) {
            $this->client->setApplicationName($this->config->getApplicationName());
        }

        $this->client->setScopes($this->config->getScopes());

        if ($this->config->getCredentialsPath()) {
            $this->client->setAuthConfig($this->config->getCredentialsPath());
        }

        // Set up UserRefreshCredentials if we have OAuth credentials
        if ($this->config->getClientId() && $this->config->getClientSecret() && $this->config->getRefreshToken()) {
            $this->credentials = new UserRefreshCredentials(
                $this->config->getScopes(),
                [
                    'client_id' => $this->config->getClientId(),
                    'client_secret' => $this->config->getClientSecret(),
                    'refresh_token' => $this->config->getRefreshToken(),
                    'token' => $this->config->getAccessToken(),
                ]
            );
        } else {
            // Fallback to old method
            if ($this->config->getClientId() && $this->config->getClientSecret()) {
                $this->client->setClientId($this->config->getClientId());
                $this->client->setClientSecret($this->config->getClientSecret());
            }

            if ($this->config->getAccessToken()) {
                $this->client->setAccessToken($this->config->getAccessToken());
            }

            if ($this->config->getRefreshToken()) {
                $this->client->setRefreshToken($this->config->getRefreshToken());
            }
        }
    }

    public function getAnalyticsService(): Analytics
    {
        if (! $this->analyticsService) {
            $this->ensureFreshToken();
            $this->analyticsService = new Analytics($this->client);
        }

        return $this->analyticsService;
    }

    public function getAnalyticsDataService(): AnalyticsData
    {
        if (! $this->analyticsDataService) {
            $this->ensureFreshToken();
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

    public function getCurrentAccessToken(): ?array
    {
        return $this->client->getAccessToken();
    }

    private function ensureFreshToken(): void
    {
        if ($this->credentials) {
            // Use UserRefreshCredentials to get a fresh token (same as working jobs)
            $accessToken = $this->credentials->fetchAuthToken();
            $this->client->setAccessToken($accessToken);
        } else {
            // Fallback to old method
            $this->checkAndRefreshToken();
        }
    }

    private function checkAndRefreshToken(): void
    {
        if ($this->client->isAccessTokenExpired()) {
            $this->client->fetchAccessTokenWithRefreshToken();
        }
    }
}
