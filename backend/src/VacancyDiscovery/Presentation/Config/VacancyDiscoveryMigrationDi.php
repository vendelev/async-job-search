<?php

declare(strict_types=1);

namespace App\VacancyDiscovery\Presentation\Config;

use App\Core\Domain\MigrationProvider;
use App\Core\Presentation\Config\MigrationProviderTag;
use App\VacancyDiscovery\Infrastructure\VacancyDiscoveryMigrationProvider;
use Thesis\Dic;
use Thesis\Dic\Module;
use Thesis\Dic\Ref;

/**
 * @implements Module<Ref<MigrationProvider>>
 */
final readonly class VacancyDiscoveryMigrationDi implements Module
{
    /**
     * Регистрирует миграции VacancyDiscovery.
     *
     * @return Ref<MigrationProvider>
     */
    public function configure(Dic $dic): Ref
    {
        return $dic
            ->object(VacancyDiscoveryMigrationProvider::class)
            ->tag(new MigrationProviderTag());
    }
}
