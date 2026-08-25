<?php

declare(strict_types=1);

namespace Tests\Suite\Platform\Postgres\Presentation\Config;

use App\Platform\Postgres\Domain\PostgresDatabase;
use App\Platform\Postgres\Presentation\Config\PostgresConfig;
use App\Platform\Postgres\Presentation\Config\PostgresModule;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use Thesis\Dic;

use function Amp\async;

final class PostgresModuleTest extends TestCase
{
    #[Test]
    #[TestDox('Создаёт подключённую к PostgreSQL базу данных')]
    public function itCreatesDatabaseConnectedToPostgres(): void
    {
        async(static function (): void {
            Dic::run(
                new PostgresModule(PostgresConfig::fromEnvironment()),
                static function (PostgresDatabase $database): void {
                    $result = $database->execute('SELECT 1 AS value');

                    self::assertSame(['value' => 1], $result->fetchRow());
                },
            );
        })->await();
    }
}
