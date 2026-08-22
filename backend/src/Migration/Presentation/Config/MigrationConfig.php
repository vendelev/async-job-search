<?php

declare(strict_types=1);

namespace App\Migration\Presentation\Config;

use InvalidArgumentException;

final readonly class MigrationConfig
{
    /**
     * @throws InvalidArgumentException Если SQLITE_DATABASE_PATH не задан
     */
    public static function fromEnvironment(): self
    {
        $databasePath = getenv('SQLITE_DATABASE_PATH');

        if ($databasePath === false || $databasePath === '') {
            throw new InvalidArgumentException('Не задана переменная окружения SQLITE_DATABASE_PATH.');
        }

        return new self($databasePath);
    }

    public function __construct(
        public string $databasePath,
    ) {}
}
