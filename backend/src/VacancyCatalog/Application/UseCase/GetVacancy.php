<?php

declare(strict_types=1);

namespace App\VacancyCatalog\Application\UseCase;

use App\VacancyCatalog\Domain\Dto\Vacancy;
use App\VacancyCatalog\Domain\VacancyCatalog;
use App\VacancyDiscovery\Domain\ExternalVacancyId;

final readonly class GetVacancy
{
    public function __construct(
        private VacancyCatalog $catalog,
    ) {
    }

    /**
     * Возвращает карточку вакансии по внешнему идентификатору.
     */
    public function execute(ExternalVacancyId $id): ?Vacancy
    {
        return $this->catalog->get($id);
    }
}
