<?php

declare(strict_types=1);

namespace App;

use App\Platform\EventBus\Presentation\Config\EventBusDi;
use App\Platform\EventStore\Presentation\Config\EventStoreDi;
use App\Platform\Logging\Presentation\Config\LoggergDi;
use App\Platform\Postgres\Presentation\Config\PostgresEnv;
use App\Platform\Postgres\Presentation\Config\PostgresDi;
use App\VacancyCatalog\Presentation\Config\VacancyCatalogEventSubscriberDi;
use App\VacancyDiscovery\Presentation\Config\HabrCareerEnv;
use App\VacancyDiscovery\Presentation\Config\HabrCareerDi;
use App\VacancyDiscovery\Presentation\Config\VacancyDiscoveryDi;
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
        private PostgresEnv $postgresConfig,
        private HabrCareerEnv $habrCareerEnv,
    ) {
    }

    /**
     * Собирает зависимости периодического поиска вакансий.
     *
     * @return Ref<DiscoverVacanciesDaemon>
     */
    public function configure(Dic $dic): Ref
    {
        $database = $dic->import(new PostgresDi($this->postgresConfig));
        $logger = $dic->import(new LoggergDi());
        $eventStore = $dic->import(new EventStoreDi($database));
        $catalogSubscriber = $dic->import(new VacancyCatalogEventSubscriberDi($database));
        $eventBus = $dic->import(new EventBusDi($eventStore, [$catalogSubscriber]));
        $dic->import(new HabrCareerDi($this->habrCareerEnv));

        $discoverVacancies = $dic->import(new VacancyDiscoveryDi($database, $eventBus, $logger));

        return $dic
            ->object(DiscoverVacanciesDaemon::class)
            ->args([
                'discoverVacancies' => $discoverVacancies,
                'logger' => $logger,
            ]);
    }
}
