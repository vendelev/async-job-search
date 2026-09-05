<?php

declare(strict_types=1);

namespace App\Platform\Migration\Domain;

use InvalidArgumentException;

final readonly class Migration
{
    /**
     * @throws InvalidArgumentException Если версия или SQL-код пусты
     */
    public function __construct(
        public string $version,
        public string $sql,
    ) {
        if ($version === '') {
            throw new InvalidArgumentException('Версия миграции не должна быть пустой.');
        }

        if ($sql === '') {
            throw new InvalidArgumentException('SQL-код миграции не должен быть пустым.');
        }
    }
}
