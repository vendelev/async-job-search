<?php

declare(strict_types=1);

namespace Tests\Suite\VacancyDiscovery\Application\UseCase;

use Stringable;
use Amp\Cancellation;
use Amp\DeferredCancellation;
use App\Platform\EventBus\Domain\DomainEvent;
use App\Platform\EventBus\Domain\EventBus;
use App\VacancyDiscovery\Application\UseCase\DiscoverVacancies;
use App\VacancyDiscovery\Domain\Dto\ExternalVacancy;
use App\VacancyDiscovery\Domain\ExternalVacancyId;
use App\VacancyDiscovery\Domain\VacancyDeduplicator;
use App\VacancyDiscovery\Domain\VacancySource;
use Closure;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use RuntimeException;

use function Amp\delay;

final class DiscoverVacanciesTest extends TestCase
{
    #[Test]
    #[TestDox('Передаёт отмену в источник вакансий')]
    public function itPassesCancellationToTheVacancySource(): void
    {
        $deferredCancellation = new DeferredCancellation();
        $source = new class implements VacancySource {
            public ?Cancellation $receivedCancellation = null;

            public function vacancies(?Cancellation $cancellation = null): iterable
            {
                $this->receivedCancellation = $cancellation;

                return [];
            }
        };
        $useCase = new DiscoverVacancies(
            [$source],
            $this->deduplicator(),
            $this->eventBus(),
        );

        $cancellation = $deferredCancellation->getCancellation();
        $useCase->execute($cancellation);

        self::assertSame($cancellation, $source->receivedCancellation);
    }

    #[Test]
    #[TestDox('Логирует ошибку источника и завершает остальные источники группы')]
    public function itLogsSourceErrorsAfterTheSourceBatchCompletes(): void
    {
        $logger = new class extends AbstractLogger {
            /**
             * @var list<array{level: mixed, message: string|Stringable, context: array<mixed>}>
             */
            public array $records = [];

            public function log($level, string|Stringable $message, array $context = []): void
            {
                $this->records[] = [
                    'level' => $level,
                    'message' => $message,
                    'context' => $context,
                ];
            }
        };
        $eventBus = $this->eventBus();
        $useCase = new DiscoverVacancies(
            [
                $this->sourceFrom(static function (): iterable {
                    throw new RuntimeException('Источник недоступен.');
                }),
                $this->source($this->vacancy('hh', '42')),
            ],
            $this->deduplicator(),
            $eventBus,
            $logger,
        );

        $useCase->execute();

        self::assertCount(1, $eventBus->events);
        self::assertCount(1, $logger->records);
        self::assertSame('error', $logger->records[0]['level']);
        self::assertInstanceOf(RuntimeException::class, $logger->records[0]['context']['exception']);
    }

    #[Test]
    #[TestDox('Не запускает следующую группу источников после отмены')]
    public function itDoesNotStartTheNextSourceBatchAfterCancellation(): void
    {
        $cancellation = new DeferredCancellation();
        $startedSources = [];
        $useCase = new DiscoverVacancies(
            [
                $this->sourceFrom(static function () use (&$startedSources, $cancellation): iterable {
                    $startedSources[] = 'first';
                    $cancellation->cancel();

                    return [];
                }),
                $this->sourceFrom(static function () use (&$startedSources): iterable {
                    $startedSources[] = 'second';

                    return [];
                }),
                $this->sourceFrom(static function () use (&$startedSources): iterable {
                    $startedSources[] = 'third';

                    return [];
                }),
                $this->sourceFrom(static function () use (&$startedSources): iterable {
                    $startedSources[] = 'fourth';

                    return [];
                }),
            ],
            $this->deduplicator(),
            $this->eventBus(),
        );

        $useCase->execute($cancellation->getCancellation());

        self::assertSame(['first', 'second', 'third'], $startedSources);
    }

    #[Test]
    #[TestDox('Запускает источники вакансий параллельно')]
    public function itStartsVacancySourcesConcurrently(): void
    {
        $secondSourceStarted = false;
        $firstSourceObservedSecondSource = false;
        $useCase = new DiscoverVacancies(
            [
                $this->sourceFrom(static function () use (
                    &$secondSourceStarted,
                    &$firstSourceObservedSecondSource,
                ): iterable {
                    delay(0.01);
                    $firstSourceObservedSecondSource = $secondSourceStarted;

                    return [];
                }),
                $this->sourceFrom(static function () use (&$secondSourceStarted): iterable {
                    $secondSourceStarted = true;

                    return [];
                }),
            ],
            $this->deduplicator(),
            $this->eventBus(),
        );

        $useCase->execute();

        self::assertTrue($this->sourceWasObservedConcurrently($firstSourceObservedSecondSource));
    }

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

            public function vacancies(?Cancellation $cancellation = null): iterable
            {
                return $this->vacancies;
            }
        };
    }

    /**
     * @param Closure(): iterable<ExternalVacancy> $vacancies
     */
    private function sourceFrom(Closure $vacancies): VacancySource
    {
        return new readonly class ($vacancies) implements VacancySource {
            /**
             * @param Closure(): iterable<ExternalVacancy> $vacancies
             */
            public function __construct(
                private Closure $vacancies,
            ) {
            }

            public function vacancies(?Cancellation $cancellation = null): iterable
            {
                return ($this->vacancies)();
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

    private function sourceWasObservedConcurrently(bool $value): bool
    {
        return $value;
    }
}
