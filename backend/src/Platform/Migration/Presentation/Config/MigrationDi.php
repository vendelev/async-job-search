<?php

declare(strict_types=1);

namespace App\Platform\Migration\Presentation\Config;

use App\Platform\Migration\Application\UseCase\ApplyMigrations;
use App\Platform\Migration\Domain\MigrationProvider;
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
     * @param list<Ref<MigrationProvider>> $providers
     */
    public function __construct(
        private Ref $database,
        private array $providers,
    ) {
    }

    /**
     * Регистрирует PostgreSQL-мигратор и команду его запуска.
     *
     * @return Ref<MigrateCommand>
     */
    public function configure(Dic $dic): Ref
    {
        $storage = $dic
            ->object(PostgresMigrationStorage::class)
            ->doNotAutowire()
            ->arg('database', $this->database)
            ->bind(objectT(MigrationStorage::class));

        $applyMigrations = $dic
            ->object(ApplyMigrations::class)
            ->doNotAutowire()
            ->args([
                'storage' => $storage,
                'providers' => $this->providers,
            ]);

        return $dic
            ->object(MigrateCommand::class)
            ->doNotAutowire()
            ->arg('applyMigrations', $applyMigrations);
    }
}
