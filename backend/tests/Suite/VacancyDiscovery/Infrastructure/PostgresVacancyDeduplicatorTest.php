<?php

declare(strict_types=1);

namespace Tests\Suite\VacancyDiscovery\Infrastructure;

use App\Platform\Postgres\Domain\PostgresExecutor;
use App\VacancyDiscovery\Domain\ExternalVacancyId;
use App\VacancyDiscovery\Infrastructure\PostgresVacancyDeduplicator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\Suite\AppTestCase;

final class PostgresVacancyDeduplicatorTest extends AppTestCase
{
    #[Test]
    #[TestDox('Регистрирует пару идентификаторов только один раз, но допускает одинаковый ID другого источника')]
    public function itRegistersAnIdentifierPairOnlyOnce(): void
    {
        $this->withinTransaction(function (PostgresExecutor $database): void {
            $deduplicator = new PostgresVacancyDeduplicator($database);

            self::assertTrue($deduplicator->register(new ExternalVacancyId('hh', '42')));
            self::assertFalse($deduplicator->register(new ExternalVacancyId('hh', '42')));
            self::assertTrue($deduplicator->register(new ExternalVacancyId('talanto', '42')));
        });
    }
}
