<?php

declare(strict_types=1);

namespace Tests\Suite\VacancyCatalog\Application\UseCase;

use App\VacancyCatalog\Application\UseCase\GetVacancy;
use App\VacancyCatalog\Domain\Dto\Vacancy;
use App\VacancyCatalog\Domain\VacancyCatalog;
use App\VacancyDiscovery\Domain\Dto\ExternalVacancy;
use App\VacancyDiscovery\Domain\ExternalVacancyId;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

final class GetVacancyTest extends TestCase
{
    #[Test]
    #[TestDox('Передаёт составной внешний идентификатор в каталог')]
    public function itGetsVacancyByExternalIdentifier(): void
    {
        $vacancy = new Vacancy(
            'habr-career',
            '42',
            'PHP developer',
            'https://example.test/42',
            'Acme',
            'Remote',
            null,
            [],
        );
        $catalog = new class ($vacancy) implements VacancyCatalog {
            public ?ExternalVacancyId $receivedId = null;

            public function __construct(
                private readonly Vacancy $vacancy,
            ) {
            }

            public function add(ExternalVacancy $vacancy): void
            {
            }

            public function list(): array
            {
                return [];
            }

            public function get(ExternalVacancyId $id): Vacancy
            {
                $this->receivedId = $id;

                return $this->vacancy;
            }
        };
        $useCase = new GetVacancy($catalog);
        $id = new ExternalVacancyId('habr-career', '42');

        self::assertSame($vacancy, $useCase->execute($id));
        self::assertSame($id, $catalog->receivedId);
    }
}
