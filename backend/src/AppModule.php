<?php

declare(strict_types=1);

namespace App;

use App\Platform\EventStore\Presentation\Config\EventStoreMigrationModule;
use App\Platform\Migration\Presentation\Config\MigrationModule;
use App\Platform\Migration\Presentation\Console\MigrateCommand;
use App\Platform\Postgres\Presentation\Config\PostgresConfig;
use App\Platform\Postgres\Presentation\Config\PostgresModule;
use Thesis\Dic;
use Thesis\Dic\Module;
use Thesis\Dic\Ref;

/**
 * @implements Module<Ref<MigrateCommand>>
 */
final readonly class AppModule implements Module
{
    public function __construct(
        private PostgresConfig $postgresConfig,
    ) {
    }

    /**
     * Собирает технические модули приложения.
     */
    public function configure(Dic $dic): mixed
    {
        $database = $dic->import(new PostgresModule($this->postgresConfig));
        $eventStoreMigrations = $dic->import(new EventStoreMigrationModule());

        return $dic->import(new MigrationModule($database, [$eventStoreMigrations]));
    }
}
