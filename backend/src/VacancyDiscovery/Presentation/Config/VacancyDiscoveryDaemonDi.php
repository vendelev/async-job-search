<?php

declare(strict_types=1);

namespace App\VacancyDiscovery\Presentation\Config;

use App\Core\Presentation\Config\ConsoleCommandTag;
use App\Platform\EventBus\Presentation\Config\EventBusDi;
use App\Platform\EventStore\Presentation\Config\EventStoreDi;
use App\Platform\Postgres\Domain\PostgresDatabase;
use App\VacancyCatalog\Presentation\Config\VacancyCatalogEventSubscriberDi;
use App\VacancyDiscovery\Presentation\Console\DiscoverVacanciesDaemon;
use Closure;
use Psr\Log\LoggerInterface;
use Thesis\Dic;
use Thesis\Dic\Module;
use Thesis\Dic\Ref;

/**
 * @implements Module<Ref<DiscoverVacanciesDaemon>>
 */
final readonly class VacancyDiscoveryDaemonDi implements Module
{
    /**
     * @param Ref<PostgresDatabase> $database
     * @param Ref<LoggerInterface> $logger
     * @param Closure(): HabrCareerEnv $habrCareerConfig
     */
    public function __construct(
        private Ref $database,
        private Ref $logger,
        private Closure $habrCareerConfig,
    ) {
    }

    /**
     * Регистрирует периодический поиск вакансий.
     *
     * @return Ref<DiscoverVacanciesDaemon>
     */
    public function configure(Dic $dic): Ref
    {
        $eventStore = $dic->import(new EventStoreDi($this->database));
        $catalogSubscriber = $dic->import(new VacancyCatalogEventSubscriberDi($this->database));
        $eventBus = $dic->import(new EventBusDi($eventStore, [$catalogSubscriber]));
        $dic->import(new HabrCareerDi($this->habrCareerConfig));
        $discoverVacancies = $dic->import(new VacancyDiscoveryDi($this->database, $eventBus, $this->logger));

        return $dic
            ->object(DiscoverVacanciesDaemon::class)
            ->args([
                'discoverVacancies' => $discoverVacancies,
                'logger' => $this->logger,
            ])
            ->lazy()
            ->tag(new ConsoleCommandTag());
    }
}
