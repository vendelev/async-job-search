<?php

declare(strict_types=1);

namespace App\Platform\Migration\Application\UseCase;

use App\Platform\Migration\Domain\Migration;
use App\Platform\Migration\Domain\MigrationProvider;
use App\Platform\Migration\Domain\MigrationStorage;
use RuntimeException;
use Throwable;

final readonly class ApplyMigrations
{
    /**
     * @param iterable<MigrationProvider> $providers
     */
    public function __construct(
        private MigrationStorage $storage,
        private iterable $providers,
    ) {
    }

    /**
     * Применяет все ещё не выполненные миграции в порядке их версий.
     *
     * @throws RuntimeException Если опубликованы миграции с одинаковой версией
     * @throws Throwable Если provider или хранилище не смогли обработать миграцию
     */
    public function execute(): void
    {
        $this->storage->initialize();

        foreach ($this->sortedMigrations() as $migration) {
            if (!$this->storage->isApplied($migration)) {
                $this->storage->apply($migration);
            }
        }
    }

    /**
     * @return list<Migration>
     *
     * @throws RuntimeException Если опубликованы миграции с одинаковой версией
     */
    private function sortedMigrations(): array
    {
        /** @var array<string, Migration> $migrations */
        $migrations = [];

        foreach ($this->providers as $provider) {
            foreach ($provider->migrations() as $migration) {
                if (isset($migrations[$migration->version])) {
                    throw new RuntimeException(
                        sprintf('Версия миграции "%s" опубликована более одного раза.', $migration->version),
                    );
                }

                $migrations[$migration->version] = $migration;
            }
        }

        usort(
            $migrations,
            static fn (Migration $left, Migration $right): int => $left->version <=> $right->version,
        );

        return $migrations;
    }
}
