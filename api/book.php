<?php

declare(strict_types=1);

header('Content-Type: application/json');

require __DIR__ . '/googleClient.php';

$payload = json_decode(file_get_contents('php://input') ?: '[]', true) ?? [];

$startTime = $payload['startTime'] ?? null;
$endTime = $payload['endTime'] ?? null;
$attendeeName = trim($payload['attendeeName'] ?? 'Guest');
$attendeeEmail = trim($payload['attendeeEmail'] ?? '');
$attendeePhone = trim($payload['attendeePhone'] ?? '');
$notes = trim($payload['notes'] ?? '');

if (!$startTime || !$endTime || !$attendeeEmail) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Missing required fields: startTime, endTime, attendeeEmail.',
    ]);
    exit;
}

try {
    $client = getGoogleClient();
    $calendar = new Google_Service_Calendar($client);

    $event = new Google_Service_Calendar_Event([
        'summary' => sprintf('Meeting with %s', $attendeeName),
        'description' => buildDescription($notes, $attendeePhone),
        'start' => ['dateTime' => $startTime],
        'end' => ['dateTime' => $endTime],
        'attendees' => [
            ['email' => $attendeeEmail],
        ],
    ]);

    $created = $calendar->events->insert(
        GOOGLE_CALENDAR_ID,
        $event,
        ['sendUpdates' => 'all']
    );

    echo json_encode([
        'success' => true,
        'eventId' => $created->getId(),
    ]);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $exception->getMessage(),
    ]);
}

function buildDescription(string $notes, string $phone): string
{
    $lines = [];

    if (!empty($notes)) {
        $lines[] = "Notes: {$notes}";
    }

    if (!empty($phone)) {
        $lines[] = "Phone: {$phone}";
    }

    return implode("\n", $lines);
}

