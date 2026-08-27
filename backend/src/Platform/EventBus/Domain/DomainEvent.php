<?php

declare(strict_types=1);

namespace App\Platform\EventBus\Domain;

use Thesis\Message\Event;

interface DomainEvent extends Event
{
    /**
     * Возвращает имя потока, в который будет добавлено событие.
     */
    public function streamName(): string;

    /**
     * Возвращает JSON-совместимые данные события для неизменяемого журнала.
     *
     * @return array<string, mixed>
     */
    public function payload(): array;
}
