<?php

declare(strict_types=1);

namespace App\VacancyDiscovery\Infrastructure;

use App\Platform\Postgres\Domain\PostgresExecutor;
use App\VacancyDiscovery\Domain\ExternalVacancyId;
use App\VacancyDiscovery\Domain\VacancyDeduplicator;

final readonly class PostgresVacancyDeduplicator implements VacancyDeduplicator
{
    public function __construct(
        private PostgresExecutor $database,
    ) {
    }

    /**
     * Регистрирует идентификаторы через атомарную вставку.
     */
    public function register(ExternalVacancyId $id): bool
    {
        $result = $this->database->execute(
            <<<'SQL'
                INSERT INTO vacancy_discovery_seen_vacancies (source, external_vacancy_id)
                VALUES (:source, :external_vacancy_id)
                ON CONFLICT (source, external_vacancy_id) DO NOTHING
                RETURNING source
                SQL,
            [
                'source' => $id->source,
                'external_vacancy_id' => $id->externalVacancyId,
            ],
        );

        return iterator_to_array($result, false) !== [];
    }
}
