<?php

declare(strict_types=1);

namespace App\VacancyDiscovery\Domain;

use App\VacancyDiscovery\Domain\Dto\ExternalVacancy;

interface VacancySource
{
    /**
     * Возвращает вакансии, найденные во внешнем источнике.
     *
     * @return iterable<ExternalVacancy>
     */
    public function vacancies(): iterable;
}
