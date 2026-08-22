<?php

declare(strict_types=1);

namespace App\Migration\Infrastructure;

use PDO;

final readonly class PostgresPdoFactory
{
    public function __construct(
        private string $dsn,
        private string $user,
        private string $password,
        private ?string $schema = null,
    ) {
    }

    /**
     * Создаёт PDO-соединение с PostgreSQL.
     */
    public function create(): PDO
    {
        $pdo = new PDO(
            $this->dsn,
            $this->user,
            $this->password,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
        );

        if ($this->schema !== null) {
            $pdo->exec(sprintf('SET search_path TO "%s"', str_replace('"', '""', $this->schema)));
        }

        return $pdo;
    }
}
