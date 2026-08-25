<?php

declare(strict_types=1);

namespace App\Platform\Postgres\Domain;

use Amp\Postgres\PostgresResult;

interface PostgresExecutor
{
    /**
     * Выполняет SQL-запрос с именованными параметрами.
     *
     * @param array<string, mixed> $params
     */
    public function execute(string $sql, array $params = []): PostgresResult;
}
