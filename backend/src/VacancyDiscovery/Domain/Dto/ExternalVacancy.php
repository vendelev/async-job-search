<?php

declare(strict_types=1);

namespace App\VacancyDiscovery\Domain\Dto;

use App\VacancyDiscovery\Domain\ExternalVacancyId;

final readonly class ExternalVacancy
{
    /**
     * @param array<string, mixed> $details Дополнительные данные для будущего каталога вакансий
     */
    public function __construct(
        public string $source,
        public string $externalVacancyId,
        public string $title,
        public string $url,
        public ?string $employerName = null,
        public ?string $location = null,
        public ?string $description = null,
        public array $details = [],
    ) {
    }

    /**
     * Возвращает составной идентификатор вакансии во внешнем источнике.
     */
    public function id(): ExternalVacancyId
    {
        return new ExternalVacancyId($this->source, $this->externalVacancyId);
    }

    /**
     * Возвращает JSON-совместимые данные для передачи в каталог вакансий.
     *
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return [
            'source' => $this->source,
            'externalVacancyId' => $this->externalVacancyId,
            'title' => $this->title,
            'url' => $this->url,
            'employerName' => $this->employerName,
            'location' => $this->location,
            'description' => $this->description,
            'details' => $this->details,
        ];
    }
}
