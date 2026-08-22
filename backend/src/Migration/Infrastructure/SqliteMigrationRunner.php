<?php

declare(strict_types=1);

namespace App\Migration\Infrastructure;

use App\Migration\Domain\Migration;
use App\Migration\Domain\MigrationProvider;
use PDO;
use RuntimeException;
use Throwable;

final readonly class SqliteMigrationRunner
{
    /**
     * @param iterable<MigrationProvider> $providers
     */
    public function __construct(
        private PDO $pdo,
        private iterable $providers,
    ) {}

    /**
     * Применяет все ещё не выполненные миграции в порядке их версий.
     *
     * @throws RuntimeException Если опубликованы миграции с одинаковой версией
     * @throws Throwable Если provider или SQLite не смогли обработать миграцию
     */
    public function migrate(): void
    {
        $this->pdo->exec(
            <<<'SQL'
                CREATE TABLE IF NOT EXISTS schema_migrations (
                    version TEXT PRIMARY KEY,
                    applied_at TEXT NOT NULL
                )
                SQL,
        );

        foreach ($this->sortedMigrations() as $migration) {
            if ($this->isApplied($migration)) {
                continue;
            }

            $this->pdo->beginTransaction();

            try {
                $this->pdo->exec($migration->sql);

                $statement = $this->pdo->prepare(
                    'INSERT INTO schema_migrations (version, applied_at) VALUES (:version, :applied_at)',
                );
                $statement->execute([
                    'version' => $migration->version,
                    'applied_at' => gmdate('c'),
                ]);

                $this->pdo->commit();
            } catch (Throwable $error) {
                if ($this->pdo->inTransaction()) {
                    $this->pdo->rollBack();
                }

                throw $error;
            }
        }
    }

    /**
     * @return list<Migration>
     *
     * @throws RuntimeException Если опубликованы миграции с одинаковой версией
     */
    private function sortedMigrations(): array
    {
        /** @var array<string, Migration> $migrations */
        $migrations = [];

        foreach ($this->providers as $provider) {
            foreach ($provider->migrations() as $migration) {
                if (isset($migrations[$migration->version])) {
                    throw new RuntimeException(
                        sprintf('Версия миграции "%s" опубликована более одного раза.', $migration->version),
                    );
                }

                $migrations[$migration->version] = $migration;
            }
        }

        $migrations = array_values($migrations);

        usort(
            $migrations,
            static fn(Migration $left, Migration $right): int => $left->version <=> $right->version,
        );

        return $migrations;
    }

    private function isApplied(Migration $migration): bool
    {
        $statement = $this->pdo->prepare(
            'SELECT 1 FROM schema_migrations WHERE version = :version LIMIT 1',
        );
        $statement->execute(['version' => $migration->version]);

        return $statement->fetchColumn() !== false;
    }
}
