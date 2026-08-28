<?php

declare(strict_types=1);

namespace App;

use App\Platform\EventBus\Presentation\Config\EventBusModule;
use App\Platform\EventStore\Presentation\Config\EventStoreModule;
use App\Platform\Logging\Presentation\Config\LoggingModule;
use App\Platform\Postgres\Presentation\Config\PostgresConfig;
use App\Platform\Postgres\Presentation\Config\PostgresModule;
use App\VacancyDiscovery\Presentation\Config\HabrCareerConfig;
use App\VacancyDiscovery\Presentation\Config\HabrCareerModule;
use App\VacancyDiscovery\Presentation\Config\VacancyDiscoveryModule;
use App\VacancyDiscovery\Presentation\Console\DiscoverVacanciesDaemon;
use Thesis\Dic;
use Thesis\Dic\Module;
use Thesis\Dic\Ref;

/**
 * @implements Module<Ref<DiscoverVacanciesDaemon>>
 */
final readonly class VacancyDiscoveryDaemonModule implements Module
{
    public function __construct(
        private PostgresConfig $postgresConfig,
        private HabrCareerConfig $habrCareerConfig,
    ) {
    }

    /**
     * Собирает зависимости периодического поиска вакансий.
     *
     * @return Ref<DiscoverVacanciesDaemon>
     */
    public function configure(Dic $dic): Ref
    {
        $database = $dic->import(new PostgresModule($this->postgresConfig));
        $logger = $dic->import(new LoggingModule());
        $eventStore = $dic->import(new EventStoreModule($database));
        $eventBus = $dic->import(new EventBusModule($eventStore));
        $source = $dic->import(new HabrCareerModule($this->habrCareerConfig));

        $discoverVacancies = $dic->import(new VacancyDiscoveryModule($database, $eventBus, $logger));

        return $dic
            ->object(DiscoverVacanciesDaemon::class)
            ->doNotAutowire()
            ->args([
                'discoverVacancies' => $discoverVacancies,
                'logger' => $logger,
            ]);
    }
}
