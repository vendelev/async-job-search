<?php

declare(strict_types=1);

namespace Tests\Suite;

use App\VacancyDiscoveryDaemonModule;
use App\Platform\Postgres\Presentation\Config\PostgresEnv;
use App\VacancyDiscovery\Presentation\Config\HabrCareerEnv;
use App\VacancyDiscovery\Presentation\Console\DiscoverVacanciesDaemon;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Thesis\Dic;

final class DaemonModuleTest extends AppTestCase
{
    #[Test]
    #[TestDox('Собирает daemon поиска вакансий')]
    public function itBuildsVacancyDiscoveryDaemon(): void
    {
        $daemonBuilt = false;

        Dic::run(
            new VacancyDiscoveryDaemonModule(
                PostgresEnv::fromEnvironment(),
                new HabrCareerEnv('test-cookie'),
            ),
            static function (DiscoverVacanciesDaemon $daemon) use (&$daemonBuilt): void {
                $daemonBuilt = true;
            },
        );

        self::assertTrue($daemonBuilt);
    }
}
