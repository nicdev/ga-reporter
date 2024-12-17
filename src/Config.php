<?php

namespace Nicdev\GoogleAnalytics;

class Config
{
    private string $credentialsPath;
    private ?string $accessToken = null;
    private array $scopes;
    private ?string $applicationName = null;

    public function __construct(array $config)
    {
        if (!isset($config['credentials_path']) && !isset($config['credentials'])) {
            throw new \InvalidArgumentException('Either credentials_path or credentials must be provided');
        }

        $this->credentialsPath = $config['credentials_path'] ?? null;
        $this->accessToken = $config['access_token'] ?? null;
        $this->applicationName = $config['application_name'] ?? null;
        $this->scopes = $config['scopes'] ?? [
            'https://www.googleapis.com/auth/analytics',
            'https://www.googleapis.com/auth/analytics.readonly'
        ];
    }

    public function getCredentialsPath(): ?string
    {
        return $this->credentialsPath;
    }

    public function getAccessToken(): ?string
    {
        return $this->accessToken;
    }

    public function getScopes(): array
    {
        return $this->scopes;
    }

    public function getApplicationName(): ?string
    {
        return $this->applicationName;
    }
}
