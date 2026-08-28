<?php

declare(strict_types=1);

namespace Platform\Migration\Presentation\Console;

use App\MigrateModule;
use App\Platform\Migration\Presentation\Console\MigrateCommand;
use App\Platform\Postgres\Presentation\Config\PostgresConfig;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\Suite\AppTestCase;
use Thesis\Dic;

final class MigrateCommandTest extends AppTestCase
{
    #[Test]
    #[TestDox('Завершает применение миграций с кодом успеха')]
    public function itExecutes(): void
    {
        $exitCode = Dic::run(
            new MigrateModule(PostgresConfig::fromEnvironment()),
            static fn(MigrateCommand $command): int => $command->execute(),
        );

        self::assertSame(0, $exitCode);
    }
}
