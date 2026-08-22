<?php

declare(strict_types=1);

namespace Tests\Suite\Migration\Domain;

use App\Migration\Domain\Migration;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

final class MigrationTest extends TestCase
{
    #[Test]
    #[DataProvider('invalidValues')]
    #[TestDox('Отклоняет миграцию с пустым обязательным полем')]
    public function itRejects(string $version, string $sql): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Migration($version, $sql);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function invalidValues(): iterable
    {
        yield 'пустая версия' => ['', 'CREATE TABLE test (id INTEGER)'];
        yield 'пустой SQL-код' => ['module_001_create_test', ''];
    }
}
