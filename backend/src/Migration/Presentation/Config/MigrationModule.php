<?php

declare(strict_types=1);

namespace App\Migration\Presentation\Config;

use App\Migration\Domain\MigrationProvider;
use App\Migration\Infrastructure\SqlitePdoFactory;
use App\Migration\Infrastructure\SqliteMigrationRunner;
use App\Migration\Presentation\Console\MigrateCommand;
use PDO;
use Thesis\Dic;
use Thesis\Dic\Module;
use Thesis\Dic\Ref;

/**
 * @implements Module<Ref<MigrateCommand>>
 */
final readonly class MigrationModule implements Module
{
    /**
     * @param list<Ref<MigrationProvider>> $providers
     */
    public function __construct(
        private MigrationConfig $config,
        private array $providers,
    ) {}

    /**
     * Регистрирует SQLite-мигратор и команду его запуска.
     *
     * @return Ref<MigrateCommand>
     */
    public function configure(Dic $dic): Ref
    {
        $pdoFactory = $dic
            ->object(SqlitePdoFactory::class)
            ->doNotAutowire()
            ->arg('databasePath', $this->config->databasePath);

        $pdo = $dic
            ->object(PDO::class, [$pdoFactory, 'create'])
            ->doNotAutowire();

        $runner = $dic
            ->object(SqliteMigrationRunner::class)
            ->doNotAutowire()
            ->args([
                'pdo' => $pdo,
                'providers' => $this->providers,
            ]);

        return $dic
            ->object(MigrateCommand::class)
            ->doNotAutowire()
            ->arg('runner', $runner);
    }
}
