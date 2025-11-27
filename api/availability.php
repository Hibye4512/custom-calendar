<?php

declare(strict_types=1);

header('Content-Type: application/json');

require __DIR__ . '/googleClient.php';

$payload = json_decode(file_get_contents('php://input') ?: '[]', true) ?? [];

$requestedDate = $payload['date'] ?? date('Y-m-d');
$requestedTimezone = $payload['timezone'] ?? getDefaultTimezone();
$slotDurationMinutes = isset($payload['slotDurationMinutes'])
    ? max(15, (int) $payload['slotDurationMinutes'])
    : 30;

try {
    $timezone = new DateTimeZone($requestedTimezone);
    $dayStart = new DateTimeImmutable($requestedDate . ' 00:00:00', $timezone);
    $dayEnd = new DateTimeImmutable($requestedDate . ' 23:59:59', $timezone);

    $client = getGoogleClient();
    $calendar = new Google_Service_Calendar($client);
    $events = $calendar->events->listEvents(GOOGLE_CALENDAR_ID, [
        'timeMin' => $dayStart->format(DateTime::RFC3339),
        'timeMax' => $dayEnd->format(DateTime::RFC3339),
        'singleEvents' => true,
        'orderBy' => 'startTime',
    ]);

    $slots = buildSlots($dayStart, $dayEnd, $events->getItems(), $slotDurationMinutes);

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

/**
 * @param Google_Service_Calendar_Event[] $events
 */
function buildSlots(
    DateTimeImmutable $dayStart,
    DateTimeImmutable $dayEnd,
    array $events,
    int $slotMinutes
): array {
    $busyBlocks = array_map(static function (Google_Service_Calendar_Event $event): array {
        $start = $event->getStart();
        $end = $event->getEnd();

        $startTime = $start->getDateTime() ?: $start->getDate();
        $endTime = $end->getDateTime() ?: $end->getDate();

        return [
            'start' => new DateTimeImmutable($startTime),
            'end' => new DateTimeImmutable($endTime),
        ];
    }, $events);

    $slots = [];
    $cursor = $dayStart;

    while ($cursor < $dayEnd) {
        $slotEnd = $cursor->modify("+{$slotMinutes} minutes");

        if ($slotEnd > $dayEnd) {
            break;
        }

        if (isFree($cursor, $slotEnd, $busyBlocks)) {
            $slots[] = [
                'id' => $cursor->format('YmdHis'),
                'startTime' => $cursor->format(DateTime::RFC3339),
                'endTime' => $slotEnd->format(DateTime::RFC3339),
                'available' => true,
            ];
        }

        $cursor = $slotEnd;
    }

    return $slots;
}

/**
 * @param array<int, array{start: DateTimeImmutable, end: DateTimeImmutable}> $busyBlocks
 */
function isFree(
    DateTimeImmutable $slotStart,
    DateTimeImmutable $slotEnd,
    array $busyBlocks
): bool {
    foreach ($busyBlocks as $block) {
        if ($slotStart < $block['end'] && $slotEnd > $block['start']) {
            return false;
        }
    }

    return true;
}

