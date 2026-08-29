<?php

declare(strict_types=1);

namespace Tests\Suite\VacancyCatalog\Application\UseCase;

use App\VacancyCatalog\Application\UseCase\ListVacancies;
use App\VacancyCatalog\Domain\Dto\Vacancy;
use App\VacancyCatalog\Domain\Dto\VacancyListItem;
use App\VacancyCatalog\Domain\VacancyCatalog;
use App\VacancyDiscovery\Domain\Dto\ExternalVacancy;
use App\VacancyDiscovery\Domain\ExternalVacancyId;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

final class ListVacanciesTest extends TestCase
{
    #[Test]
    #[TestDox('Возвращает вакансии из каталога для списка')]
    public function itListsVacancies(): void
    {
        $vacancies = [new VacancyListItem(
            'habr-career',
            '42',
            'PHP developer',
            'https://example.test/42',
            'Acme',
            'Remote',
        )];
        $useCase = new ListVacancies(new readonly class ($vacancies) implements VacancyCatalog {
            /**
             * @param list<VacancyListItem> $vacancies
             */
            public function __construct(
                private array $vacancies,
            ) {
            }

            public function add(ExternalVacancy $vacancy): void
            {
            }

            public function list(): array
            {
                return $this->vacancies;
            }

            public function get(ExternalVacancyId $id): ?Vacancy
            {
                return null;
            }
        });

        self::assertSame($vacancies, $useCase->execute());
    }
}
