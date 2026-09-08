<?php

declare(strict_types=1);

namespace App;

use App\Core\Presentation\Config\ConsoleCommandTag;
use App\Platform\EventStore\Presentation\Config\EventStoreMigrationDi;
use App\Platform\Logging\Presentation\Config\LoggergDi;
use App\Platform\Migration\Presentation\Config\MigrationDi;
use App\Platform\Postgres\Presentation\Config\PostgresDi;
use App\Platform\Postgres\Presentation\Config\PostgresEnv;
use App\Platform\WebServer\Presentation\Config\HttpDi;
use App\Platform\WebServer\Presentation\Config\HttpServerEnv;
use App\VacancyCatalog\Presentation\Config\VacancyCatalogMigrationDi;
use App\VacancyDiscovery\Presentation\Config\HabrCareerEnv;
use App\VacancyDiscovery\Presentation\Config\VacancyDiscoveryDaemonDi;
use App\VacancyDiscovery\Presentation\Config\VacancyDiscoveryMigrationDi;
use Symfony\Component\Console\Application;
use Thesis\Dic;
use Thesis\Dic\Module;
use Thesis\Dic\Ref;

/**
 * @implements Module<Ref<Application>>
 */
final readonly class AppModule implements Module
{
    public function __construct(
        private PostgresEnv $postgresConfig,
        private HttpServerEnv $httpConfig,
        private HabrCareerEnv $habrCareerConfig,
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

        $logger = $dic->import(new LoggergDi());
        $dic->import(new HttpDi($database, $logger, $this->httpConfig));
        $dic->import(new VacancyDiscoveryDaemonDi($database, $logger, $this->habrCareerConfig));

        $commands = $dic->taggedList(ConsoleCommandTag::class);

        return $dic
            ->object(Application::class)
            ->call('setName', ['name' => 'async-job-search'])
            ->call('setAutoExit', ['boolean' => false])
            ->call('addCommands', ['commands' => $commands]);
    }
}
