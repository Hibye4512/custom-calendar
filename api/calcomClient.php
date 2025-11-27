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

use GuzzleHttp\Client;

function getCalcomClient(): Client
{
    return new Client([
        'base_uri' => CALCOM_API_BASE_URL,
        'headers' => [
            'Authorization' => 'Bearer ' . CALCOM_API_KEY,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ],
        'timeout' => 30,
    ]);
}

function getDefaultTimezone(): string
{
    return defined('DEFAULT_TIMEZONE') ? DEFAULT_TIMEZONE : 'UTC';
}

/**
 * Get event type ID from slug
 * Cal.com API requires event type ID, so we fetch it from the slug
 */
function getEventTypeId(): ?int
{
    static $cachedId = null;
    
    if ($cachedId !== null) {
        return $cachedId;
    }
    
    try {
        $client = getCalcomClient();
        $response = $client->get('/event-types');
        $data = json_decode($response->getBody()->getContents(), true);
        
        // Find event type matching the slug
        foreach ($data['event_types'] ?? [] as $eventType) {
            if (isset($eventType['slug']) && $eventType['slug'] === CALCOM_EVENT_SLUG) {
                $cachedId = (int) $eventType['id'];
                return $cachedId;
            }
        }
        
        // Fallback: try to find by ID if slug is numeric
        if (is_numeric(CALCOM_EVENT_SLUG)) {
            $cachedId = (int) CALCOM_EVENT_SLUG;
            return $cachedId;
        }
        
        return null;
    } catch (Throwable $e) {
        error_log('Failed to fetch Cal.com event type ID: ' . $e->getMessage());
        return null;
    }
}

