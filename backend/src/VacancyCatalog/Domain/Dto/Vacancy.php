<?php

declare(strict_types=1);

namespace App\VacancyCatalog\Domain\Dto;

final readonly class Vacancy
{
    /**
     * @param array<string, mixed> $details
     */
    public function __construct(
        public string $source,
        public string $externalVacancyId,
        public string $title,
        public string $url,
        public ?string $employerName,
        public ?string $location,
        public ?string $description,
        public array $details,
    ) {
    }
}
