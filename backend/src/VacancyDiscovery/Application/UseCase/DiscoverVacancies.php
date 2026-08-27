<?php

declare(strict_types=1);

namespace App\VacancyDiscovery\Application\UseCase;

use App\Platform\EventBus\Domain\EventBus;
use App\VacancyDiscovery\Domain\Event\VacancyDiscovered;
use App\VacancyDiscovery\Domain\VacancyDeduplicator;
use App\VacancyDiscovery\Domain\VacancySource;

final readonly class DiscoverVacancies
{
    /**
     * @param iterable<VacancySource> $sources
     */
    public function __construct(
        private iterable $sources,
        private VacancyDeduplicator $deduplicator,
        private EventBus $eventBus,
    ) {
    }

    /**
     * Находит новые вакансии и публикует события об их обнаружении.
     */
    public function execute(): void
    {
        foreach ($this->sources as $source) {
            foreach ($source->vacancies() as $vacancy) {
                if (!$this->deduplicator->register($vacancy->id())) {
                    continue;
                }

                $this->eventBus->publish(new VacancyDiscovered($vacancy));
            }
        }
    }
}
