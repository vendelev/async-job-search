<?php

declare(strict_types=1);

namespace App\Platform\Postgres\Domain;

use Amp\Postgres\PostgresTransaction;
use Amp\Sql\SqlException;

interface PostgresDatabase extends PostgresExecutor
{
    /**
     * Начинает транзакцию на одном соединении пула.
     *
     * @throws SqlException Если PostgreSQL недоступен
     */
    public function beginTransaction(): PostgresTransaction;
}
