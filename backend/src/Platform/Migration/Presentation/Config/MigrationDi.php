<?php

declare(strict_types=1);

namespace App\Platform\Migration\Presentation\Config;

use App\Core\Presentation\Config\ConsoleCommandTag;
use App\Core\Presentation\Config\MigrationProviderTag;
use App\Platform\Migration\Application\UseCase\ApplyMigrations;
use App\Platform\Migration\Domain\MigrationStorage;
use App\Platform\Migration\Infrastructure\PostgresMigrationStorage;
use App\Platform\Migration\Presentation\Console\MigrateCommand;
use App\Platform\Postgres\Domain\PostgresDatabase;
use Thesis\Dic;
use Thesis\Dic\Module;
use Thesis\Dic\Ref;

use function Typhoon\Type\objectT;

/**
 * @implements Module<Ref<MigrateCommand>>
 */
final readonly class MigrationDi implements Module
{
    /**
     * @param Ref<PostgresDatabase> $database
     */
    public function __construct(
        private Ref $database,
    ) {
    }

    /**
     * Регистрирует PostgreSQL-мигратор и команду его запуска.
     *
     * @return Ref<MigrateCommand>
     */
    public function configure(Dic $dic): Ref
    {
        $dic
            ->object(PostgresMigrationStorage::class)
            ->arg('database', $this->database)
            ->bind(objectT(MigrationStorage::class));

        $providers = $dic->taggedList(MigrationProviderTag::class);
        $applyMigrations = $dic
            ->object(ApplyMigrations::class)
            ->args([
                'providers' => $providers,
            ])
            ->bind(objectT(ApplyMigrations::class));

        return $dic
            ->object(MigrateCommand::class)
            ->arg('applyMigrations', $dic->provider($applyMigrations))
            ->tag(new ConsoleCommandTag());
    }
}
