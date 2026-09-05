<?php

declare(strict_types=1);

namespace App\Platform\EventBus\Domain;

interface EventSubscriber
{
    /**
     * Возвращает класс поддерживаемого Domain-события.
     *
     * @return class-string<DomainEvent>
     */
    public function subscribedTo(): string;

    /**
     * Обрабатывает доставленное событие.
     */
    public function handle(DomainEvent $event): void;
}
