<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$configPath = realpath(__DIR__ . '/../secure-config/config.php');

if (!$configPath || !file_exists($configPath)) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Missing secure-config/config.php. Please copy config.sample.php and update your secrets.',
    ]);
    exit;
}

require $configPath;

function getGoogleClient(): Google_Client
{
    $client = new Google_Client();
    $client->setAuthConfig(GOOGLE_KEY_PATH);
    $client->setScopes([
        Google_Service_Calendar::CALENDAR,
    ]);

    return $client;
}

function getDefaultTimezone(): string
{
    return defined('DEFAULT_TIMEZONE') ? DEFAULT_TIMEZONE : 'UTC';
}

