<?php

declare(strict_types=1);

namespace Platform\Migration\Presentation\Console;

use App\Platform\EventStore\Presentation\Config\EventStoreMigrationDi;
use App\Platform\Migration\Presentation\Config\MigrationDi;
use App\Platform\Migration\Presentation\Console\MigrateCommand;
use App\Platform\Postgres\Presentation\Config\PostgresDi;
use App\Platform\Postgres\Presentation\Config\PostgresEnv;
use App\VacancyCatalog\Presentation\Config\VacancyCatalogMigrationDi;
use App\VacancyDiscovery\Presentation\Config\VacancyDiscoveryMigrationDi;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\Suite\AppTestCase;
use Thesis\Dic;
use Thesis\Dic\Module;
use Thesis\Dic\Ref;

final class MigrateCommandTest extends AppTestCase
{
    #[Test]
    #[TestDox('Завершает применение миграций с кодом успеха')]
    public function itExecutes(): void
    {
        $exitCode = Dic::run(
            new readonly class implements Module {
                /**
                 * @return Ref<MigrateCommand>
                 */
                public function configure(Dic $dic): Ref
                {
                    $database = $dic->import(new PostgresDi(
                        static fn(): PostgresEnv => PostgresEnv::fromEnvironment(),
                    ));
                    $migrate = $dic->import(new MigrationDi($database));
                    $dic->import(new EventStoreMigrationDi());
                    $dic->import(new VacancyCatalogMigrationDi());
                    $dic->import(new VacancyDiscoveryMigrationDi());

                    return $migrate;
                }
            },
            static fn(MigrateCommand $command): int => $command(),
        );

        self::assertSame(0, $exitCode);
    }
}
