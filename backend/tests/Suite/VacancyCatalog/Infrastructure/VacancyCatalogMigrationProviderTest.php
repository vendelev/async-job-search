<?php

declare(strict_types=1);

namespace Tests\Suite\VacancyCatalog\Infrastructure;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\Suite\AppTestCase;

use function Amp\async;

final class VacancyCatalogMigrationProviderTest extends AppTestCase
{
    #[Test]
    #[TestDox('Создаёт таблицу пользовательской проекции вакансий')]
    public function itCreatesVacancyCatalogTable(): void
    {
        $table = async(fn (): ?array => $this->database->execute(
            'SELECT table_name FROM information_schema.tables '
            . 'WHERE table_schema = :schema AND table_name = :table_name',
            ['schema' => 'public', 'table_name' => 'vacancy_catalog_vacancies'],
        )->fetchRow())->await();

        self::assertSame(['table_name' => 'vacancy_catalog_vacancies'], $table);
    }
}
