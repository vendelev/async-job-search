<?php

declare(strict_types=1);

namespace App\Platform\Migration\Presentation\Console;

use App\Platform\Migration\Application\UseCase\ApplyMigrations;
use Symfony\Component\Console\Attribute\AsCommand;
use Throwable;

use function Amp\async;

#[AsCommand(name: 'migrate', description: 'Применяет миграции PostgreSQL.')]
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
    public function __invoke(): int
    {
        async(function (): void {
            $this->applyMigrations->execute();
        })->await();

        return 0;
    }
}
