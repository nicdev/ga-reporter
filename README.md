# Google Analytics PHP Client

A modern PHP client for the Google Analytics API 

## Installation

```bash
composer require nicdev/ga-reporter
```

## Setup

1. Create a Google Cloud Project
2. Enable the Google Analytics API
3. Create credentials (OAuth 2.0 or Service Account)
4. Download your credentials JSON file

## Usage

```php
// Initialize with credentials
$client = new \Nicdev\GoogleAnalytics\Client([
    'credentials_path' => '/path/to/credentials.json'
]);

// Create Reporter instance
$reporter = new \Nicdev\GoogleAnalytics\Reporter($client);

// Get page views for last 30 days
$startDate = new DateTime('30 days ago');
$endDate = new DateTime();
$pageViews = $reporter->getPageViews('VIEW_ID', $startDate, $endDate);

// Get real-time users
$activeUsers = $reporter->getRealTimeUsers('VIEW_ID');
```

## Features

- Universal Analytics (UA) and GA4 support
- Real-time reporting
- Audience metrics
- Event tracking
- Custom dimensions and metrics
- Automatic token refresh
- PSR-4 compliant

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.
