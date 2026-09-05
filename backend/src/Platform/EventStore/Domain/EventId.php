<?php

declare(strict_types=1);

namespace App\Platform\EventStore\Domain;

use InvalidArgumentException;
use Ramsey\Uuid\Uuid;

final readonly class EventId
{
    private function __construct(
        public string $value,
    ) {
    }

    /**
     * Генерирует UUIDv7 для нового события.
     */
    public static function generate(): self
    {
        return new self(Uuid::uuid7()->toString());
    }

    /**
     * @throws InvalidArgumentException Если строка не является UUIDv7
     */
    public static function fromString(string $value): self
    {
        if (!Uuid::isValid($value) || Uuid::fromString($value)->getVersion() !== 7) {
            throw new InvalidArgumentException('Идентификатор события должен быть UUIDv7');
        }

        return new self($value);
    }
}
