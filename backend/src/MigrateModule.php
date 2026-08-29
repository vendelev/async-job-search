<?php

declare(strict_types=1);

namespace App;

use App\Platform\EventStore\Presentation\Config\EventStoreMigrationDi;
use App\Platform\Migration\Presentation\Config\MigrationDi;
use App\Platform\Migration\Presentation\Console\MigrateCommand;
use App\Platform\Postgres\Presentation\Config\PostgresEnv;
use App\Platform\Postgres\Presentation\Config\PostgresDi;
use App\VacancyCatalog\Presentation\Config\VacancyCatalogMigrationDi;
use App\VacancyDiscovery\Presentation\Config\VacancyDiscoveryMigrationDi;
use Thesis\Dic;
use Thesis\Dic\Module;
use Thesis\Dic\Ref;

/**
 * @implements Module<Ref<MigrateCommand>>
 */
final readonly class MigrateModule implements Module
{
    public function __construct(
        private PostgresEnv $postgresConfig,
    ) {
    }

    /**
     * Собирает технические модули приложения.
     */
    public function configure(Dic $dic): mixed
    {
        $database = $dic->import(new PostgresDi($this->postgresConfig));
        return $dic->import(new MigrationDi(
            $database,
            [
                $dic->import(new EventStoreMigrationDi()),
                $dic->import(new VacancyCatalogMigrationDi()),
                $dic->import(new VacancyDiscoveryMigrationDi()),
            ]
        ));
    }
}
