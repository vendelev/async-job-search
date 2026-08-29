<?php

declare(strict_types=1);

namespace Tests\Suite\VacancyCatalog\Infrastructure;

use App\Platform\Postgres\Domain\PostgresExecutor;
use App\VacancyCatalog\Infrastructure\PostgresVacancyCatalog;
use App\VacancyDiscovery\Domain\Dto\ExternalVacancy;
use App\VacancyDiscovery\Domain\ExternalVacancyId;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\Suite\AppTestCase;

final class PostgresVacancyCatalogTest extends AppTestCase
{
    #[Test]
    #[TestDox('Сохраняет вакансию один раз и читает список с карточкой')]
    public function itStoresAndReadsVacancyProjection(): void
    {
        $this->withinTransaction(function (PostgresExecutor $database): void {
            $catalog = new PostgresVacancyCatalog($database);
            $vacancy = new ExternalVacancy(
                'habr-career',
                '42',
                'PHP developer',
                'https://example.test/42',
                'Acme',
                'Remote',
                'Develop backend services.',
                ['salary' => '200000'],
            );

            $catalog->add($vacancy);
            $catalog->add(new ExternalVacancy('habr-career', '42', 'Changed title', 'https://example.test/changed'));

            self::assertCount(1, $catalog->list());
            self::assertSame('PHP developer', $catalog->list()[0]->title);

            $storedVacancy = $catalog->get(new ExternalVacancyId('habr-career', '42'));

            self::assertNotNull($storedVacancy);
            self::assertSame('Develop backend services.', $storedVacancy->description);
            self::assertSame(['salary' => '200000'], $storedVacancy->details);
            self::assertNull($catalog->get(new ExternalVacancyId('habr-career', 'missing')));
        });
    }
}
