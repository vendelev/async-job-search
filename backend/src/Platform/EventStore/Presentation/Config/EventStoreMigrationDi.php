<?php

declare(strict_types=1);

namespace App\Platform\EventStore\Presentation\Config;

use App\Core\Domain\MigrationProvider;
use App\Core\Presentation\Config\MigrationProviderTag;
use App\Platform\EventStore\Infrastructure\EventStoreMigrationProvider;
use Thesis\Dic;
use Thesis\Dic\Module;
use Thesis\Dic\Ref;

/**
 * @implements Module<Ref<MigrationProvider>>
 */
final readonly class EventStoreMigrationDi implements Module
{
    /**
     * Регистрирует миграции EventStore.
     *
     * @return Ref<MigrationProvider>
     */
    public function configure(Dic $dic): Ref
    {
        return $dic
            ->object(EventStoreMigrationProvider::class)
            ->tag(new MigrationProviderTag());
    }
}
