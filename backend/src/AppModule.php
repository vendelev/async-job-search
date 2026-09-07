<?php

declare(strict_types=1);

namespace App;

use App\Core\Presentation\Config\ConsoleCommandTag;
use App\Platform\EventStore\Presentation\Config\EventStoreMigrationDi;
use App\Platform\Migration\Presentation\Config\MigrationDi;
use App\Platform\Postgres\Presentation\Config\PostgresDi;
use App\Platform\Postgres\Presentation\Config\PostgresEnv;
use App\Platform\WebServer\Presentation\Config\HttpDi;
use App\Platform\WebServer\Presentation\Config\HttpServerEnv;
use App\VacancyCatalog\Presentation\Config\VacancyCatalogMigrationDi;
use App\VacancyDiscovery\Presentation\Config\HabrCareerEnv;
use App\VacancyDiscovery\Presentation\Config\VacancyDiscoveryDaemonDi;
use App\VacancyDiscovery\Presentation\Config\VacancyDiscoveryMigrationDi;
use Closure;
use Symfony\Component\Console\Application;
use Thesis\Dic;
use Thesis\Dic\Module;
use Thesis\Dic\Ref;

/**
 * @implements Module<Ref<Application>>
 */
final readonly class AppModule implements Module
{
    /**
     * @param Closure(): PostgresEnv $postgresConfig
     * @param Closure(): HttpServerEnv $httpConfig
     * @param Closure(): HabrCareerEnv $habrCareerConfig
     */
    public function __construct(
        private Closure $postgresConfig,
        private Closure $httpConfig,
        private Closure $habrCareerConfig,
    ) {
    }

    /**
     * Собирает все консольные команды приложения.
     *
     * @return Ref<Application>
     */
    public function configure(Dic $dic): Ref
    {
        $database = $dic->import(new PostgresDi($this->postgresConfig));
        $dic->import(new MigrationDi($database));
        $dic->import(new EventStoreMigrationDi());
        $dic->import(new VacancyCatalogMigrationDi());
        $dic->import(new VacancyDiscoveryMigrationDi());

        $dic->import(new HttpDi($database, $this->httpConfig));
        $dic->import(new VacancyDiscoveryDaemonDi($database, $this->habrCareerConfig));

        $commands = $dic->taggedList(ConsoleCommandTag::class);

        return $dic
            ->object(Application::class)
            ->call('setName', ['name' => 'async-job-search'])
            ->call('addCommands', ['commands' => $commands]);
    }
}
