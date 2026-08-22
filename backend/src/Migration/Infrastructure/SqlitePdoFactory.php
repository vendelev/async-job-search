<?php

declare(strict_types=1);

namespace App\Migration\Infrastructure;

use PDO;
use RuntimeException;

final readonly class SqlitePdoFactory
{
    public function __construct(
        private string $databasePath,
    ) {}

    /**
     * Создаёт соединение с SQLite, подготавливая директорию для файла базы данных.
     *
     * @throws RuntimeException Если директорию базы данных нельзя создать
     */
    public function create(): PDO
    {
        $directory = dirname($this->databasePath);

        if (!is_dir($directory) && !mkdir($directory, recursive: true) && !is_dir($directory)) {
            throw new RuntimeException(sprintf('Не удалось создать директорию базы данных "%s".', $directory));
        }

        return new PDO(
            'sqlite:' . $this->databasePath,
            options: [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
        );
    }
}
