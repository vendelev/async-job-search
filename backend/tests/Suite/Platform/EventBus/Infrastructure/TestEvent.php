<?php

declare(strict_types=1);

namespace Tests\Suite\Platform\EventBus\Infrastructure;

use App\Platform\EventBus\Domain\DomainEvent;

final readonly class TestEvent implements DomainEvent
{
    /**
     * @param array<string, mixed> $eventPayload
     */
    public function __construct(
        private string $stream,
        private array $eventPayload,
    ) {
    }

    public function streamName(): string
    {
        return $this->stream;
    }

    public function payload(): array
    {
        return $this->eventPayload;
    }
}
