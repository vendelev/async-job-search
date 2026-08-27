<?php

declare(strict_types=1);

namespace App\Platform\EventStore\Domain;

interface EventStore
{
    /**
     * Добавляет событие в неизменяемую историю его потока.
     */
    public function append(StoredEvent $event): void;

    /**
     * Возвращает все события указанного потока в порядке их добавления в журнал.
     *
     * @return list<StoredEvent>
     */
    public function history(string $streamName): array;
}
