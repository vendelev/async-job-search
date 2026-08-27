<?php

declare(strict_types=1);

namespace Tests\Suite\VacancyDiscovery\Application\UseCase;

use App\Platform\EventBus\Domain\DomainEvent;
use App\Platform\EventBus\Domain\EventBus;
use App\VacancyDiscovery\Application\UseCase\DiscoverVacancies;
use App\VacancyDiscovery\Domain\Dto\ExternalVacancy;
use App\VacancyDiscovery\Domain\ExternalVacancyId;
use App\VacancyDiscovery\Domain\VacancyDeduplicator;
use App\VacancyDiscovery\Domain\VacancySource;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

final class DiscoverVacanciesTest extends TestCase
{
    #[Test]
    #[TestDox('Публикует событие только для новой вакансии источника')]
    public function itPublishesAnEventOnlyForANewVacancyFromASource(): void
    {
        $eventBus = $this->eventBus();
        $useCase = new DiscoverVacancies(
            [$this->source($this->vacancy('hh', '42')), $this->source($this->vacancy('hh', '42'))],
            $this->deduplicator(),
            $eventBus,
        );

        $useCase->execute();

        self::assertCount(1, $eventBus->events);
        self::assertSame('vacancy:hh:42', $eventBus->events[0]->streamName());
    }

    #[Test]
    #[TestDox('Считает одинаковые внешние идентификаторы разных источников разными вакансиями')]
    public function itTreatsTheSameExternalIdFromDifferentSourcesAsDifferentVacancies(): void
    {
        $eventBus = $this->eventBus();
        $useCase = new DiscoverVacancies(
            [$this->source($this->vacancy('hh', '42')), $this->source($this->vacancy('talanto', '42'))],
            $this->deduplicator(),
            $eventBus,
        );

        $useCase->execute();

        self::assertCount(2, $eventBus->events);
        self::assertSame(['vacancy:hh:42', 'vacancy:talanto:42'], array_map(
            static fn(DomainEvent $event): string => $event->streamName(),
            $eventBus->events,
        ));
    }

    #[Test]
    #[TestDox('Не публикует событие, когда регистрация вакансии не удалась')]
    public function itDoesNotPublishAnEventWhenVacancyRegistrationIsNotNew(): void
    {
        $eventBus = $this->eventBus();
        $useCase = new DiscoverVacancies(
            [$this->source($this->vacancy('hh', '42'))],
            new class implements VacancyDeduplicator {
                public function register(ExternalVacancyId $id): bool
                {
                    return false;
                }
            },
            $eventBus,
        );

        $useCase->execute();

        self::assertSame([], $eventBus->events);
    }

    private function vacancy(string $source, string $externalVacancyId): ExternalVacancy
    {
        return new ExternalVacancy(
            source: $source,
            externalVacancyId: $externalVacancyId,
            title: 'PHP developer',
            url: 'https://example.test/vacancies/' . $externalVacancyId,
            employerName: 'Acme',
            location: 'Remote',
            description: 'Develop backend services.',
        );
    }

    private function source(ExternalVacancy ...$vacancies): VacancySource
    {
        return new readonly class (array_values($vacancies)) implements VacancySource {
            /**
             * @param list<ExternalVacancy> $vacancies
             */
            public function __construct(
                private array $vacancies,
            ) {
            }

            public function vacancies(): iterable
            {
                return $this->vacancies;
            }
        };
    }

    /**
     * @return object{events: list<DomainEvent>}&EventBus
     */
    private function eventBus(): object
    {
        return new class implements EventBus {
            /**
             * @var list<DomainEvent>
             */
            public array $events = [];

            public function publish(DomainEvent $event): void
            {
                $this->events[] = $event;
            }
        };
    }

    private function deduplicator(): VacancyDeduplicator
    {
        return new class implements VacancyDeduplicator {
            /**
             * @var array<string, true>
             */
            private array $registered = [];

            public function register(ExternalVacancyId $id): bool
            {
                $key = $id->source . ':' . $id->externalVacancyId;

                if (isset($this->registered[$key])) {
                    return false;
                }

                $this->registered[$key] = true;

                return true;
            }
        };
    }
}
