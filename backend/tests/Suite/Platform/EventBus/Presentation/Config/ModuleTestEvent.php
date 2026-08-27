<?php

declare(strict_types=1);

namespace Tests\Suite\Platform\EventBus\Presentation\Config;

use App\Platform\EventBus\Domain\DomainEvent;

final readonly class ModuleTestEvent implements DomainEvent
{
    public function __construct(
        private string $stream,
    ) {
    }

    public function streamName(): string
    {
        return $this->stream;
    }

    public function payload(): array
    {
        return [];
    }
}
