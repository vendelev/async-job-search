<?php

declare(strict_types=1);

namespace Tests\Suite\VacancyCatalog\Presentation\Listener;

use App\VacancyCatalog\Domain\Dto\Vacancy;
use App\VacancyCatalog\Domain\VacancyCatalog;
use App\VacancyCatalog\Presentation\Listener\VacancyDiscoveredSubscriber;
use App\VacancyDiscovery\Domain\Dto\ExternalVacancy;
use App\VacancyDiscovery\Domain\Event\VacancyDiscovered;
use App\VacancyDiscovery\Domain\ExternalVacancyId;
use LogicException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

final class VacancyDiscoveredSubscriberTest extends TestCase
{
    /**
     * @throws LogicException Если подписчик отклонил переданное событие
     */
    #[Test]
    #[TestDox('Сохраняет вакансию из события обнаружения')]
    public function itAddsDiscoveredVacancyToCatalog(): void
    {
        $catalog = new class implements VacancyCatalog {
            public ?ExternalVacancy $addedVacancy = null;

            public function add(ExternalVacancy $vacancy): void
            {
                $this->addedVacancy = $vacancy;
            }

            public function list(): array
            {
                return [];
            }

            public function get(ExternalVacancyId $id): ?Vacancy
            {
                return null;
            }
        };
        $vacancy = new ExternalVacancy('habr-career', '42', 'PHP developer', 'https://example.test/42');

        new VacancyDiscoveredSubscriber($catalog)->handle(new VacancyDiscovered($vacancy));

        self::assertSame($vacancy, $catalog->addedVacancy);
    }
}
