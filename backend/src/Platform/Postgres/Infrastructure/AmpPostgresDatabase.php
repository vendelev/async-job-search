<?php

declare(strict_types=1);

namespace App\Platform\Postgres\Infrastructure;

use Amp\Postgres\PostgresConnectionPool;
use Amp\Postgres\PostgresResult;
use Amp\Postgres\PostgresTransaction;
use Amp\Sql\SqlException;
use Amp\Sql\SqlQueryError;
use App\Platform\Postgres\Domain\PostgresDatabase;

final readonly class AmpPostgresDatabase implements PostgresDatabase
{
    public function __construct(
        private PostgresConnectionPool $pool,
    ) {
    }

    /**
     * @param array<string, mixed> $params
     *
     * @throws SqlException Если PostgreSQL недоступен
     * @throws SqlQueryError Если SQL-запрос содержит ошибку
     */
    public function execute(string $sql, array $params = []): PostgresResult
    {
        return $this->pool->execute($sql, $params);
    }

    /**
     * @throws SqlException Если PostgreSQL недоступен
     */
    public function beginTransaction(): PostgresTransaction
    {
        return $this->pool->beginTransaction();
    }
}
