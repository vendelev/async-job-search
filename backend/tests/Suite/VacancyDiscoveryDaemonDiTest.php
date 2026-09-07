<?php

declare(strict_types=1);

namespace Tests\Suite;

use Throwable;
use App\Platform\Logging\Presentation\Config\LoggergDi;
use App\Platform\Postgres\Presentation\Config\PostgresDi;
use App\Platform\Postgres\Presentation\Config\PostgresEnv;
use App\VacancyDiscovery\Presentation\Config\HabrCareerEnv;
use App\VacancyDiscovery\Presentation\Config\VacancyDiscoveryDaemonDi;
use App\VacancyDiscovery\Presentation\Console\DiscoverVacanciesDaemon;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use ReflectionObject;
use Thesis\Dic;
use Thesis\Dic\Module;
use Thesis\Dic\Ref;

final class VacancyDiscoveryDaemonDiTest extends AppTestCase
{
    /**
     * @throws Throwable
     */
    #[Test]
    #[TestDox('Собирает контекст поиска вакансий')]
    public function itBuildsVacancyDiscoveryContext(): void
    {
        $daemonBuilt = false;

        Dic::run(
            new readonly class implements Module {
                /**
                 * @return Ref<DiscoverVacanciesDaemon>
                 */
                public function configure(Dic $dic): Ref
                {
                    $database = $dic->import(new PostgresDi(
                        static fn(): PostgresEnv => new PostgresEnv(
                            'postgres',
                            5432,
                            'async_job_search_test',
                            'async_job_search_test',
                            'test-password',
                        ),
                    ));
                    $logger = $dic->import(new LoggergDi());

                    return $dic->import(new VacancyDiscoveryDaemonDi(
                        $database,
                        $logger,
                        static fn(): HabrCareerEnv => new HabrCareerEnv('test-cookie'),
                    ));
                }
            },
            static function (DiscoverVacanciesDaemon $daemon) use (&$daemonBuilt): void {
                // DiscoverVacanciesDaemon регистрируется как lazy-объект, поэтому граф зависимостей
                // собирается только при инициализации прокси.
                new ReflectionObject($daemon)->initializeLazyObject($daemon);
                $daemonBuilt = true;
            },
        );

        self::assertTrue($daemonBuilt);
    }
}
