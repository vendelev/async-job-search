<?php

declare(strict_types=1);

namespace App\VacancyDiscovery\Presentation\Config;

use App\Platform\EventBus\Domain\EventBus;
use App\Platform\Postgres\Domain\PostgresDatabase;
use App\VacancyDiscovery\Application\UseCase\DiscoverVacancies;
use App\VacancyDiscovery\Domain\VacancyDeduplicator;
use App\VacancyDiscovery\Infrastructure\PostgresVacancyDeduplicator;
use Psr\Log\LoggerInterface;
use Thesis\Dic;
use Thesis\Dic\Module;
use Thesis\Dic\Ref;

use function Typhoon\Type\objectT;

/**
 * @implements Module<Ref<DiscoverVacancies>>
 */
final readonly class VacancyDiscoveryDi implements Module
{
    /**
     * @param Ref<PostgresDatabase> $database
     * @param Ref<EventBus> $eventBus
     * @param Ref<LoggerInterface> $logger
     */
    public function __construct(
        private Ref $database,
        private Ref $eventBus,
        private Ref $logger,
    ) {
    }

    /**
     * Регистрирует use case поиска вакансий и PostgreSQL-дедупликацию.
     *
     * @return Ref<DiscoverVacancies>
     */
    public function configure(Dic $dic): Ref
    {
        $sources = $dic->taggedList(VacancySourceTag::class);
        $deduplicator = $dic
            ->object(PostgresVacancyDeduplicator::class)
            ->doNotAutowire()
            ->arg('database', $this->database)
            ->bind(objectT(VacancyDeduplicator::class));

        return $dic
            ->object(DiscoverVacancies::class)
            ->doNotAutowire()
            ->args([
                'sources' => $sources,
                'deduplicator' => $deduplicator,
                'eventBus' => $this->eventBus,
                'logger' => $this->logger,
            ]);
    }
}
