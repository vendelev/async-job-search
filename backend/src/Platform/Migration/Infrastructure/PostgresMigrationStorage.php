<?php

declare(strict_types=1);

namespace App\Platform\Migration\Infrastructure;

use Amp\Sql\SqlException;
use Amp\Sql\SqlTransactionError;
use App\Platform\Migration\Domain\Migration;
use App\Platform\Migration\Domain\MigrationStorage;
use App\Platform\Postgres\Domain\PostgresDatabase;
use Throwable;

final readonly class PostgresMigrationStorage implements MigrationStorage
{
    public function __construct(
        private PostgresDatabase $database,
    ) {
    }

    public function initialize(): void
    {
        $this->database->execute(
            <<<'SQL'
                CREATE TABLE IF NOT EXISTS schema_migrations (
                    version TEXT PRIMARY KEY,
                    applied_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
                )
                SQL,
        );
    }

    public function isApplied(Migration $migration): bool
    {
        $result = $this->database->execute(
            'SELECT 1 FROM schema_migrations WHERE version = :version LIMIT 1',
            ['version' => $migration->version],
        );

        return $result->fetchRow() !== null;
    }

    /**
     * @throws SqlTransactionError Если операция с транзакцией PostgreSQL завершилась ошибкой
     * @throws SqlException Если PostgreSQL недоступен
     */
    public function apply(Migration $migration): void
    {
        $transaction = $this->database->beginTransaction();

        try {
            $transaction->execute($migration->sql);
            $transaction->execute(
                'INSERT INTO schema_migrations (version) VALUES (:version)',
                ['version' => $migration->version],
            );
            $transaction->commit();
        } catch (Throwable $error) {
            if ($transaction->isActive()) {
                $transaction->rollback();
            }

            throw $error;
        }
    }
}
