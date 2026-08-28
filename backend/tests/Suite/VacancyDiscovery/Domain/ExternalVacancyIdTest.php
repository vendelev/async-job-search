<?php

declare(strict_types=1);

namespace Tests\Suite\VacancyDiscovery\Domain;

use App\VacancyDiscovery\Domain\ExternalVacancyId;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

final class ExternalVacancyIdTest extends TestCase
{
    #[Test]
    #[TestDox('Отклоняет пустой идентификатор источника')]
    public function itRejectsAnEmptySource(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ExternalVacancyId('', '42');
    }

    #[Test]
    #[TestDox('Отклоняет пустой внешний идентификатор вакансии')]
    public function itRejectsAnEmptyExternalVacancyId(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ExternalVacancyId('hh', '');
    }
}
