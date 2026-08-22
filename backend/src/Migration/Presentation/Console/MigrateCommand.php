<?php

declare(strict_types=1);

namespace App\Migration\Presentation\Console;

use App\Migration\Infrastructure\SqliteMigrationRunner;

final readonly class MigrateCommand
{
    public function __construct(
        private SqliteMigrationRunner $runner,
    ) {}

    /**
     * Запускает применение миграций.
     */
    public function execute(): int
    {
        $this->runner->migrate();

        return 0;
    }
}
