<?php

declare(strict_types=1);

namespace App\VacancyDiscovery\Application\UseCase;

use Amp\Future;
use Amp\Cancellation;
use App\Platform\EventBus\Domain\EventBus;
use App\VacancyDiscovery\Domain\Event\VacancyDiscovered;
use App\VacancyDiscovery\Domain\VacancyDeduplicator;
use App\VacancyDiscovery\Domain\VacancySource;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

use function Amp\async;
use function Amp\Future\awaitAll;

final readonly class DiscoverVacancies
{
    private const int MAX_CONCURRENT_SOURCES = 3;

    /**
     * @param list<VacancySource> $sources
     */
    public function __construct(
        private array $sources,
        private VacancyDeduplicator $deduplicator,
        private EventBus $eventBus,
        private LoggerInterface $logger = new NullLogger(),
    ) {
    }

    /**
     * Находит новые вакансии и публикует события об их обнаружении.
     */
    public function execute(?Cancellation $cancellation = null): void
    {
        $futures = [];
        $batchSources = [];

        foreach ($this->sources as $source) {
            if ($cancellation?->isRequested()) {
                break;
            }

            $futures[] = async($this->discover(...), $source, $cancellation);
            $batchSources[] = $source;

            if (count($futures) === self::MAX_CONCURRENT_SOURCES) {
                $this->awaitSources($futures, $batchSources);
                $futures = [];
                $batchSources = [];
            }
        }

        $this->awaitSources($futures, $batchSources);
    }

    /**
     * Находит и публикует новые вакансии одного источника.
     */
    private function discover(VacancySource $source, ?Cancellation $cancellation): int
    {
        $vacancyCount = 0;

        foreach ($source->vacancies($cancellation) as $vacancy) {
            ++$vacancyCount;

            if (!$this->deduplicator->register($vacancy->id())) {
                continue;
            }

            $this->eventBus->publish(new VacancyDiscovered($vacancy));
        }

        return $vacancyCount;
    }

    /**
     * @param list<Future<int>> $futures
     * @param list<VacancySource> $sources
     */
    private function awaitSources(array $futures, array $sources): void
    {
        [$errors, $vacancyCounts] = awaitAll($futures);

        foreach ($vacancyCounts as $index => $vacancyCount) {
            $this->logger->info('Источник вакансий завершил поиск.', [
                'source' => $sources[$index]::class,
                'vacancy_count' => $vacancyCount,
            ]);
        }

        foreach ($errors as $index => $exception) {
            $this->logger->error('Источник вакансий завершился ошибкой.', [
                'source' => $sources[$index]::class,
                'exception' => $exception,
            ]);
        }
    }
}
