<?php

declare(strict_types=1);

header('Content-Type: application/json');

require __DIR__ . '/calcomClient.php';

$payload = json_decode(file_get_contents('php://input') ?: '[]', true) ?? [];

$startTime = $payload['startTime'] ?? null;
$endTime = $payload['endTime'] ?? null;
$attendeeName = trim($payload['attendeeName'] ?? 'Guest');
$attendeeEmail = trim($payload['attendeeEmail'] ?? '');
$attendeePhone = trim($payload['attendeePhone'] ?? '');
$notes = trim($payload['notes'] ?? '');
$timezone = $payload['timezone'] ?? getDefaultTimezone();

if (!$startTime || !$endTime || !$attendeeEmail) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Missing required fields: startTime, endTime, attendeeEmail.',
    ]);
    exit;
}

try {
    $eventTypeId = getEventTypeId();
    
    if (!$eventTypeId) {
        throw new RuntimeException('Could not find Cal.com event type. Please check your CALCOM_EVENT_SLUG in config.php.');
    }

    $client = getCalcomClient();

    // Build booking payload for Cal.com
    $bookingData = [
        'eventTypeId' => $eventTypeId,
        'start' => $startTime,
        'end' => $endTime,
        'timeZone' => $timezone,
        'responses' => [
            'name' => $attendeeName,
            'email' => $attendeeEmail,
        ],
    ];

    // Add optional fields if provided
    if (!empty($attendeePhone)) {
        $bookingData['responses']['phone'] = $attendeePhone;
    }

    if (!empty($notes)) {
        $bookingData['responses']['notes'] = $notes;
    }

    // Cal.com booking endpoint
    $response = $client->post('/bookings', [
        'json' => $bookingData,
    ]);

    $booking = json_decode($response->getBody()->getContents(), true);

    // Cal.com returns booking with uid or id
    $bookingId = $booking['uid'] ?? $booking['id'] ?? 'unknown';

    echo json_encode([
        'success' => true,
        'eventId' => $bookingId,
        'booking' => $booking,
    ]);
} catch (Throwable $exception) {
    http_response_code(500);
    
    // Try to extract more detailed error from Cal.com response
    $errorMessage = $exception->getMessage();
    
    if ($exception instanceof \GuzzleHttp\Exception\ClientException) {
        $response = $exception->getResponse();
        if ($response) {
            $errorBody = json_decode($response->getBody()->getContents(), true);
            if (isset($errorBody['message'])) {
                $errorMessage = $errorBody['message'];
            }
        }
    }
    
    echo json_encode([
        'success' => false,
        'error' => $errorMessage,
    ]);
}
