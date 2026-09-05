<?php

declare(strict_types=1);

namespace App\Platform\EventBus\Domain;

interface EventBus
{
    /**
     * Сохраняет событие и запускает его обработчиков независимо друг от друга.
     */
    public function publish(DomainEvent $event): void;
}
