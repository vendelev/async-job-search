<?php

declare(strict_types=1);

namespace App\VacancyDiscovery\Domain\Event;

use App\Platform\EventBus\Domain\DomainEvent;
use App\VacancyDiscovery\Domain\Dto\ExternalVacancy;

final readonly class VacancyDiscovered implements DomainEvent
{
    public function __construct(
        public ExternalVacancy $vacancy,
    ) {
    }

    /**
     * Возвращает поток, уникальный для вакансии во внешнем источнике.
     */
    public function streamName(): string
    {
        return sprintf('vacancy:%s:%s', $this->vacancy->source, $this->vacancy->externalVacancyId);
    }

    /**
     * Возвращает данные обнаруженной вакансии.
     *
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return $this->vacancy->payload();
    }
}
