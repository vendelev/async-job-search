<?php

declare(strict_types=1);

namespace App\VacancyCatalog\Presentation\Listener;

use App\Platform\EventBus\Domain\DomainEvent;
use App\Platform\EventBus\Domain\EventSubscriber;
use App\VacancyCatalog\Domain\VacancyCatalog;
use App\VacancyDiscovery\Domain\Event\VacancyDiscovered;
use LogicException;

final readonly class VacancyDiscoveredSubscriber implements EventSubscriber
{
    public function __construct(
        private VacancyCatalog $catalog,
    ) {
    }

    /**
     * @return class-string<VacancyDiscovered>
     */
    public function subscribedTo(): string
    {
        return VacancyDiscovered::class;
    }

    /**
     * Сохраняет пользовательскую проекцию обнаруженной вакансии.
     *
     * @throws LogicException Если EventBus передал событие другого типа
     */
    public function handle(DomainEvent $event): void
    {
        if (!$event instanceof VacancyDiscovered) {
            throw new LogicException('Подписчик получил неподдерживаемое событие.');
        }

        $this->catalog->add($event->vacancy);
    }
}
