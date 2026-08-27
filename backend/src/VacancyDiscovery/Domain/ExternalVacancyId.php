<?php

declare(strict_types=1);

namespace App\VacancyDiscovery\Domain;

use InvalidArgumentException;

final readonly class ExternalVacancyId
{
    /**
     * @throws InvalidArgumentException Если идентификатор источника или вакансии пуст
     */
    public function __construct(
        public string $source,
        public string $externalVacancyId,
    ) {
        if ($source === '') {
            throw new InvalidArgumentException('Источник вакансии не должен быть пустым.');
        }

        if ($externalVacancyId === '') {
            throw new InvalidArgumentException('Внешний идентификатор вакансии не должен быть пустым.');
        }
    }
}
