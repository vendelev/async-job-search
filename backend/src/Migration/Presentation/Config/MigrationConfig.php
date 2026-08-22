<?php

declare(strict_types=1);

namespace App\Migration\Presentation\Config;

use InvalidArgumentException;

final readonly class MigrationConfig
{
    /**
     * @throws InvalidArgumentException Если обязательная переменная PostgreSQL не задана
     */
    public static function fromEnvironment(): self
    {
        $dsn = getenv('DATABASE_DSN');
        $user = getenv('DATABASE_USER');
        $password = getenv('DATABASE_PASSWORD');

        if ($dsn === false || $dsn === '') {
            throw new InvalidArgumentException('Не задана переменная окружения DATABASE_DSN.');
        }

        if ($user === false || $user === '') {
            throw new InvalidArgumentException('Не задана переменная окружения DATABASE_USER.');
        }

        if ($password === false || $password === '') {
            throw new InvalidArgumentException('Не задана переменная окружения DATABASE_PASSWORD.');
        }

        return new self($dsn, $user, $password);
    }

    public function __construct(
        public string $dsn,
        public string $user,
        public string $password,
        public ?string $schema = null,
    ) {
    }
}
