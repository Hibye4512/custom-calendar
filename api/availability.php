<?php

declare(strict_types=1);

header('Content-Type: application/json');

require __DIR__ . '/calcomClient.php';

$payload = json_decode(file_get_contents('php://input') ?: '[]', true) ?? [];

$requestedDate = $payload['date'] ?? date('Y-m-d');
$requestedTimezone = $payload['timezone'] ?? getDefaultTimezone();

try {
    $eventTypeId = getEventTypeId();
    
    if (!$eventTypeId) {
        throw new RuntimeException('Could not find Cal.com event type. Please check your CALCOM_EVENT_SLUG in config.php.');
    }

    $timezone = new DateTimeZone($requestedTimezone);
    $dayStart = new DateTimeImmutable($requestedDate . ' 00:00:00', $timezone);
    $dayEnd = new DateTimeImmutable($requestedDate . ' 23:59:59', $timezone);

    $client = getCalcomClient();
    
    // Cal.com availability endpoint
    $response = $client->get('/availability/slots', [
        'query' => [
            'eventTypeId' => $eventTypeId,
            'startTime' => $dayStart->format('c'), // ISO 8601
            'endTime' => $dayEnd->format('c'),
            'timeZone' => $requestedTimezone,
        ],
    ]);

    $data = json_decode($response->getBody()->getContents(), true);
    
    // Cal.com returns slots in format: { time: "2025-01-20T10:00:00.000Z", ... }
    // We need to transform to match frontend format
    $slots = [];
    
    if (isset($data['slots']) && is_array($data['slots'])) {
        foreach ($data['slots'] as $slot) {
            if (!isset($slot['time'])) {
                continue;
            }
            
            $startTime = new DateTimeImmutable($slot['time']);
            
            // Cal.com slots are typically 30 minutes (based on your event type)
            // We'll use the duration from the event type or default to 30 minutes
            $durationMinutes = $slot['duration'] ?? 30;
            $endTime = $startTime->modify("+{$durationMinutes} minutes");
            
            $slots[] = [
                'id' => $startTime->format('YmdHis'),
                'startTime' => $startTime->format(DateTime::RFC3339),
                'endTime' => $endTime->format(DateTime::RFC3339),
                'available' => true,
            ];
        }
    }

    echo json_encode([
        'success' => true,
        'slots' => $slots,
    ]);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $exception->getMessage(),
    ]);
}
