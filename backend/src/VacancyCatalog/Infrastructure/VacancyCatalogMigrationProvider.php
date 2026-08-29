<?php

declare(strict_types=1);

namespace App\VacancyCatalog\Infrastructure;

use App\Platform\Migration\Domain\Migration;
use App\Platform\Migration\Domain\MigrationProvider;

final readonly class VacancyCatalogMigrationProvider implements MigrationProvider
{
    /**
     * @return iterable<Migration>
     */
    public function migrations(): iterable
    {
        yield new Migration(
            'vacancy_catalog_001_create_vacancies',
            <<<'SQL'
                CREATE TABLE vacancy_catalog_vacancies (
                    source TEXT NOT NULL,
                    external_vacancy_id TEXT NOT NULL,
                    title TEXT NOT NULL,
                    url TEXT NOT NULL,
                    employer_name TEXT NULL,
                    location TEXT NULL,
                    description TEXT NULL,
                    details JSONB NOT NULL,
                    PRIMARY KEY (source, external_vacancy_id)
                );
                SQL,
        );
    }
}
