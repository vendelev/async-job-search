<?php

declare(strict_types=1);

namespace App\Platform\Migration\Presentation\Console;

use App\Platform\Migration\Application\UseCase\ApplyMigrations;
use Throwable;

use function Amp\async;

final readonly class MigrateCommand
{
    public function __construct(
        private ApplyMigrations $applyMigrations,
    ) {
    }

    /**
     * Запускает применение миграций.
     *
     * @throws Throwable Если PostgreSQL не смог применить миграции
     */
    public function execute(): int
    {
        async(function (): void {
            $this->applyMigrations->execute();
        })->await();

        return 0;
    }
}
