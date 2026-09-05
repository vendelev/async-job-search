<?php

declare(strict_types=1);

namespace App\VacancyDiscovery\Infrastructure;

use App\Platform\Migration\Domain\Migration;
use App\Platform\Migration\Domain\MigrationProvider;

final readonly class VacancyDiscoveryMigrationProvider implements MigrationProvider
{
    /**
     * @return iterable<Migration>
     */
    public function migrations(): iterable
    {
        yield new Migration(
            'vacancy_discovery_001_create_seen_vacancies',
            <<<'SQL'
                CREATE TABLE vacancy_discovery_seen_vacancies (
                    source TEXT NOT NULL,
                    external_vacancy_id TEXT NOT NULL,
                    PRIMARY KEY (source, external_vacancy_id)
                );
                SQL,
        );
    }
}
