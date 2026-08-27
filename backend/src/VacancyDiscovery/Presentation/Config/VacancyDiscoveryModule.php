<?php

declare(strict_types=1);

namespace App\VacancyDiscovery\Presentation\Config;

use App\Platform\EventBus\Domain\EventBus;
use App\Platform\Postgres\Domain\PostgresDatabase;
use App\VacancyDiscovery\Application\UseCase\DiscoverVacancies;
use App\VacancyDiscovery\Domain\VacancyDeduplicator;
use App\VacancyDiscovery\Domain\VacancySource;
use App\VacancyDiscovery\Infrastructure\PostgresVacancyDeduplicator;
use Thesis\Dic;
use Thesis\Dic\Module;
use Thesis\Dic\Ref;

use function Typhoon\Type\objectT;

/**
 * @implements Module<Ref<DiscoverVacancies>>
 */
final readonly class VacancyDiscoveryModule implements Module
{
    /**
     * @param Ref<PostgresDatabase> $database
     * @param Ref<EventBus> $eventBus
     * @param list<Ref<VacancySource>> $sources
     */
    public function __construct(
        private Ref $database,
        private Ref $eventBus,
        private array $sources,
    ) {
    }

    /**
     * Регистрирует use case поиска вакансий и PostgreSQL-дедупликацию.
     *
     * @return Ref<DiscoverVacancies>
     */
    public function configure(Dic $dic): Ref
    {
        $deduplicator = $dic
            ->object(PostgresVacancyDeduplicator::class)
            ->doNotAutowire()
            ->arg('database', $this->database)
            ->bind(objectT(VacancyDeduplicator::class));

        return $dic
            ->object(DiscoverVacancies::class)
            ->doNotAutowire()
            ->args([
                'sources' => $this->sources,
                'deduplicator' => $deduplicator,
                'eventBus' => $this->eventBus,
            ]);
    }
}
