<?php

declare(strict_types=1);

namespace App\Platform\EventStore\Domain;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use JsonException;

final readonly class StoredEvent
{
    public EventId $id;

    public DateTimeImmutable $occurredAt;

    /**
     * JSON-представление {@see self::$payload}, вычисленное при создании события.
     */
    public string $encodedPayload;

    /**
     * @param array<string, mixed> $payload
     *
     * @throws InvalidArgumentException Если обязательные поля события пусты или payload нельзя представить в JSON
     */
    public function __construct(
        public string $streamName,
        public string $type,
        DateTimeImmutable $occurredAt,
        public array $payload,
        ?EventId $id = null,
    ) {
        if ($streamName === '') {
            throw new InvalidArgumentException('Имя потока не должно быть пустым.');
        }

        if ($type === '') {
            throw new InvalidArgumentException('Тип события не должен быть пустым.');
        }

        try {
            $this->encodedPayload = json_encode($payload, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidArgumentException(
                'Payload события должен быть сериализуем в JSON.',
                $exception->getCode(),
                previous: $exception,
            );
        }

        $this->id = $id ?? EventId::generate();
        $this->occurredAt = $occurredAt->setTimezone(new DateTimeZone('UTC'));
    }
}
