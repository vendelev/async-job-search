<?php

declare(strict_types=1);

namespace App\VacancyCatalog\Domain;

use App\VacancyCatalog\Domain\Dto\Vacancy;
use App\VacancyCatalog\Domain\Dto\VacancyListItem;
use App\VacancyDiscovery\Domain\Dto\ExternalVacancy;
use App\VacancyDiscovery\Domain\ExternalVacancyId;

interface VacancyCatalog
{
    /**
     * Сохраняет вакансию, если её ещё нет в пользовательской проекции.
     */
    public function add(ExternalVacancy $vacancy): void;

    /**
     * Возвращает вакансии для списка.
     *
     * @return list<VacancyListItem>
     */
    public function list(): array;

    /**
     * Возвращает карточку вакансии по внешнему идентификатору.
     */
    public function get(ExternalVacancyId $id): ?Vacancy;
}
