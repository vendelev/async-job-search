<?php

declare(strict_types=1);

namespace App\VacancyCatalog\Presentation\Config;

use App\Platform\Migration\Domain\MigrationProvider;
use App\VacancyCatalog\Infrastructure\VacancyCatalogMigrationProvider;
use Thesis\Dic;
use Thesis\Dic\Module;
use Thesis\Dic\Ref;

/**
 * @implements Module<Ref<MigrationProvider>>
 */
final readonly class VacancyCatalogMigrationModule implements Module
{
    /**
     * Регистрирует миграции VacancyCatalog.
     *
     * @return Ref<MigrationProvider>
     */
    public function configure(Dic $dic): Ref
    {
        return $dic->object(VacancyCatalogMigrationProvider::class)->doNotAutowire();
    }
}
