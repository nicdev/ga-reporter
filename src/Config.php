<?php

/**
 * Configuration handler for Google Analytics API authentication
 *
 * @package Nicdev\GoogleAnalytics
 */

namespace Nicdev\GoogleAnalytics;

/**
 * Config class handles authentication configuration for Google Analytics
 * Supports both OAuth tokens and credential file paths
 */
class Config
{
    private ?string $_credentialsPath;
    private ?string $_accessToken = null;
    private array $_scopes;
    private ?string $_applicationName = null;
    private ?string $_clientId = null;
    private ?string $_clientSecret = null;

    /**
     * Initialize configuration for Google Analytics authentication
     *
     * @param array $config Configuration array containing either credentials file path or OAuth credentials
     *                      Required keys:
     *                      - credentials_path: Path to credentials JSON file, OR
     *                      - credentials: Array containing client_id and client_secret
     *                      Optional keys:
     *                      - access_token: OAuth access token
     *                      - application_name: Name of the application
     *                      - scopes: Array of Google Analytics API scopes
     * 
     * @throws \InvalidArgumentException If neither credentials_path nor credentials are provided
     */
    public function __construct(array $config)
    {
        if (!isset($config['credentials_path']) && !isset($config['credentials'])) {
            throw new \InvalidArgumentException('Either credentials_path or credentials must be provided');
        }

        $this->_credentialsPath = $config['credentials_path'] ?? null;
        
        if (isset($config['credentials'])) {
            $this->_clientId = $config['credentials']['client_id'] ?? null;
            $this->_clientSecret = $config['credentials']['client_secret'] ?? null;
        }

        if (!$this->_credentialsPath && !$this->_clientId && !$this->_clientSecret) {
            throw new \InvalidArgumentException('Either credentials_path or credentials must be provided');
        }

        $this->_accessToken = $config['access_token'] ?? null;
        $this->_applicationName = $config['application_name'] ?? null;
        $this->_scopes = $config['scopes'] ?? [
            'https://www.googleapis.com/auth/analytics',
            'https://www.googleapis.com/auth/analytics.readonly'
        ];
    }

    /**
     * Get the credentials file path
     *
     * @return string|null Path to the credentials JSON file
     */
    public function getCredentialsPath(): ?string
    {
        return $this->_credentialsPath;
    }

    /**
     * Get the OAuth access token
     *
     * @return string|null The OAuth access token
     */
    public function getAccessToken(): ?string
    {
        return $this->_accessToken;
    }

    /**
     * Get the configured API scopes
     *
     * @return array Array of Google Analytics API scopes
     */
    public function getScopes(): array
    {
        return $this->_scopes;
    }

    /**
     * Get the application name
     *
     * @return string|null The configured application name
     */
    public function getApplicationName(): ?string
    {
        return $this->_applicationName;
    }

    /**
     * Get the OAuth client ID
     *
     * @return string|null The OAuth client ID
     */
    public function getClientId(): ?string
    {
        return $this->_clientId;
    }

    /**
     * Get the OAuth client secret
     *
     * @return string|null The OAuth client secret
     */
    public function getClientSecret(): ?string
    {
        return $this->_clientSecret;
    }
}
