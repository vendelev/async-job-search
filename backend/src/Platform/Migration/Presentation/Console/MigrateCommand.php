<?php

declare(strict_types=1);

namespace App\Platform\Migration\Presentation\Console;

use App\Platform\Migration\Application\UseCase\ApplyMigrations;
use Closure;
use Symfony\Component\Console\Attribute\AsCommand;
use Throwable;

use function Amp\async;

#[AsCommand(name: 'migrate', description: 'Применяет миграции PostgreSQL.')]
final readonly class MigrateCommand
{
    /**
     * @param Closure(): ApplyMigrations $applyMigrations
     */
    public function __construct(
        private Closure $applyMigrations,
    ) {
    }

    /**
     * Запускает применение миграций.
     *
     * @throws Throwable Если PostgreSQL не смог применить миграции
     */
    public function __invoke(): int
    {
        $applyMigrations = ($this->applyMigrations)();

        async(function () use ($applyMigrations): void {
            $applyMigrations->execute();
        })->await();

        return 0;
    }
}
