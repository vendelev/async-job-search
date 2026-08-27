<?php

declare(strict_types=1);

namespace App\VacancyDiscovery\Domain;

interface VacancyDeduplicator
{
    /**
     * Регистрирует вакансию и сообщает, была ли её пара идентификаторов новой.
     */
    public function register(ExternalVacancyId $id): bool;
}
