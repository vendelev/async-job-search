<?php

declare(strict_types=1);

namespace App\VacancyCatalog\Application\UseCase;

use App\VacancyCatalog\Domain\Dto\VacancyListItem;
use App\VacancyCatalog\Domain\VacancyCatalog;

final readonly class ListVacancies
{
    public function __construct(
        private VacancyCatalog $catalog,
    ) {
    }

    /**
     * Возвращает вакансии для списка.
     *
     * @return list<VacancyListItem>
     */
    public function execute(): array
    {
        return $this->catalog->list();
    }
}
