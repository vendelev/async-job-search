<?php

declare(strict_types=1);

namespace App\Platform\Postgres\Infrastructure;

use Amp\Postgres\PostgresResult;
use Amp\Postgres\PostgresTransaction;
use Amp\Sql\SqlQueryError;
use App\Platform\Postgres\Domain\PostgresExecutor;

final readonly class AmpPostgresTransaction implements PostgresExecutor
{
    public function __construct(
        private PostgresTransaction $transaction,
    ) {
    }

    /**
     * @param array<string, mixed> $params
     *
     * @throws SqlQueryError Если SQL-запрос содержит ошибку
     */
    public function execute(string $sql, array $params = []): PostgresResult
    {
        return $this->transaction->execute($sql, $params);
    }
}
