<?php

declare(strict_types=1);

namespace App\Platform\EventStore\Infrastructure;

use App\Platform\EventStore\Domain\EventId;
use App\Platform\EventStore\Domain\EventStore;
use App\Platform\EventStore\Domain\StoredEvent;
use App\Platform\Postgres\Domain\PostgresExecutor;
use DateMalformedStringException;
use DateTimeImmutable;
use JsonException;
use RuntimeException;

final readonly class PostgresEventStore implements EventStore
{
    public function __construct(
        private PostgresExecutor $database,
    ) {
    }

    public function append(StoredEvent $event): void
    {
        $this->database->execute(
            <<<'SQL'
                INSERT INTO event_store_events (id, stream_name, type, occurred_at, payload)
                VALUES (:id, :stream_name, :type, :occurred_at, CAST(:payload AS jsonb))
                SQL,
            [
                'id' => $event->id->value,
                'stream_name' => $event->streamName,
                'type' => $event->type,
                'occurred_at' => $event->occurredAt->format('Y-m-d\TH:i:s.uP'),
                'payload' => $event->encodedPayload,
            ],
        );
    }

    /**
     * @return list<StoredEvent>
     *
     * @throws JsonException Если сохранённый payload не является корректным JSON
     * @throws DateMalformedStringException Если сохранённое время невозможно разобрать
     * @throws RuntimeException Если сохранённый payload не является JSON-объектом
     */
    public function history(string $streamName): array
    {
        $result = $this->database->execute(
            <<<'SQL'
                SELECT id, stream_name, type, occurred_at, payload
                FROM event_store_events
                WHERE stream_name = :stream_name
                ORDER BY position ASC
                SQL,
            ['stream_name' => $streamName],
        );
        /** @var list<array{id: string, stream_name: string, type: string, occurred_at: string, payload: string}> $rows */
        $rows = iterator_to_array($result, false);

        return array_map($this->storedEvent(...), $rows);
    }

    /**
     * @param array{id: string, stream_name: string, type: string, occurred_at: string, payload: string} $row
     *
     * @throws JsonException Если сохранённый payload не является корректным JSON
     * @throws DateMalformedStringException Если сохранённое время невозможно разобрать
     * @throws RuntimeException Если сохранённый payload не является JSON-объектом
     */
    private function storedEvent(array $row): StoredEvent
    {
        $payload = json_decode($row['payload'], true, flags: JSON_THROW_ON_ERROR);

        if (!is_array($payload)) {
            throw new RuntimeException('Payload сохранённого события должен быть JSON-объектом.');
        }

        /** @var array<string, mixed> $payload */
        return new StoredEvent(
            $row['stream_name'],
            $row['type'],
            new DateTimeImmutable($row['occurred_at']),
            $payload,
            EventId::fromString($row['id']),
        );
    }
}
