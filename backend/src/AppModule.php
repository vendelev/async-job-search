<?php

declare(strict_types=1);

namespace App;

use App\Migration\Presentation\Config\MigrationConfig;
use App\Migration\Presentation\Config\MigrationModule;
use App\Migration\Presentation\Console\MigrateCommand;
use Thesis\Dic;
use Thesis\Dic\Module;
use Thesis\Dic\Ref;

/**
 * @implements Module<Ref<MigrateCommand>>
 */
final readonly class AppModule implements Module
{
    public function __construct(
        private MigrationConfig $migrationConfig,
    ) {
    }

    /**
     * Собирает технические модули приложения.
     */
    public function configure(Dic $dic): mixed
    {
        return $dic->import(new MigrationModule($this->migrationConfig, []));
    }
}
