<?php

declare(strict_types=1);

namespace App\Migration\Presentation\Config;

use App\Migration\Domain\MigrationProvider;
use App\Migration\Infrastructure\PostgresMigrationRunner;
use App\Migration\Infrastructure\PostgresPdoFactory;
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
    ) {
    }

    /**
     * Регистрирует PostgreSQL-мигратор и команду его запуска.
     *
     * @return Ref<MigrateCommand>
     */
    public function configure(Dic $dic): Ref
    {
        $pdoFactory = $dic
            ->object(PostgresPdoFactory::class)
            ->doNotAutowire()
            ->args([
                'dsn' => $this->config->dsn,
                'user' => $this->config->user,
                'password' => $this->config->password,
                'schema' => $this->config->schema,
            ]);

        $pdo = $dic
            ->object(PDO::class, [$pdoFactory, 'create'])
            ->doNotAutowire();

        $runner = $dic
            ->object(PostgresMigrationRunner::class)
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
