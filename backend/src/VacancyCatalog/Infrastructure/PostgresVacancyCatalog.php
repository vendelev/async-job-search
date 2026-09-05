<?php

declare(strict_types=1);

namespace App\VacancyCatalog\Infrastructure;

use App\Platform\Postgres\Domain\PostgresExecutor;
use App\VacancyCatalog\Domain\Dto\Vacancy;
use App\VacancyCatalog\Domain\Dto\VacancyListItem;
use App\VacancyCatalog\Domain\VacancyCatalog;
use App\VacancyDiscovery\Domain\Dto\ExternalVacancy;
use App\VacancyDiscovery\Domain\ExternalVacancyId;
use JsonException;

final readonly class PostgresVacancyCatalog implements VacancyCatalog
{
    public function __construct(
        private PostgresExecutor $database,
    ) {
    }

    /**
     * Сохраняет вакансию атомарной вставкой, игнорируя повторную доставку события.
     *
     * @throws JsonException Если details нельзя закодировать в JSON
     */
    public function add(ExternalVacancy $vacancy): void
    {
        $this->database->execute(
            <<<'SQL'
                INSERT INTO vacancy_catalog_vacancies (
                    source, external_vacancy_id, title, url, employer_name, location, description, details
                ) VALUES (
                    :source, :external_vacancy_id, :title, :url, :employer_name, :location, :description,
                    CAST(:details AS JSONB)
                ) ON CONFLICT (source, external_vacancy_id) DO NOTHING
                SQL,
            [
                'source' => $vacancy->source,
                'external_vacancy_id' => $vacancy->externalVacancyId,
                'title' => $vacancy->title,
                'url' => $vacancy->url,
                'employer_name' => $vacancy->employerName,
                'location' => $vacancy->location,
                'description' => $vacancy->description,
                'details' => json_encode($vacancy->details, JSON_THROW_ON_ERROR),
            ],
        );
    }

    /**
     * @return list<VacancyListItem>
     */
    public function list(): array
    {
        $result = $this->database->execute(
            <<<'SQL'
                SELECT source, external_vacancy_id, title, url, employer_name, location, details->>'salary' AS salary
                FROM vacancy_catalog_vacancies ORDER BY source, external_vacancy_id
                SQL,
        );

        $vacancies = [];

        foreach ($result as $row) {
            /**
             * @var array{
             *     source: string,
             *     external_vacancy_id: string,
             *     title: string,
             *     url: string,
             *     employer_name: ?string,
             *     location: ?string,
             *     salary: ?string
             * } $row
             */

            $vacancies[] = new VacancyListItem(
                $row['source'],
                $row['external_vacancy_id'],
                $row['title'],
                $row['url'],
                $row['employer_name'],
                $row['location'],
                $row['salary'],
            );
        }

        return $vacancies;
    }

    /**
     * @throws JsonException Если details нельзя декодировать из JSON
     */
    public function get(ExternalVacancyId $id): ?Vacancy
    {
        $row = $this->database->execute(
            <<<'SQL'
                SELECT source, external_vacancy_id, title, url, employer_name, location, description, details
                FROM vacancy_catalog_vacancies
                WHERE source = :source AND external_vacancy_id = :external_vacancy_id
                SQL,
            [
                'source' => $id->source,
                'external_vacancy_id' => $id->externalVacancyId,
            ],
        )->fetchRow();

        if ($row === null) {
            return null;
        }

        /**
         * @var array{
         *     source: string,
         *     external_vacancy_id: string,
         *     title: string,
         *     url: string,
         *     employer_name: ?string,
         *     location: ?string,
         *     description: ?string,
         *     details: string
         * } $row
         */

        /** @var array<string, mixed> $details */
        $details = json_decode($row['details'], true, 512, JSON_THROW_ON_ERROR);

        return new Vacancy(
            $row['source'],
            $row['external_vacancy_id'],
            $row['title'],
            $row['url'],
            $row['employer_name'],
            $row['location'],
            $row['description'],
            $details,
        );
    }
}
