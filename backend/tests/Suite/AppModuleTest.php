<?php

declare(strict_types=1);

namespace Tests\Suite;

use Throwable;
use App\AppModule;
use App\Platform\Postgres\Presentation\Config\PostgresEnv;
use App\Platform\WebServer\Presentation\Config\HttpServerEnv;
use App\VacancyDiscovery\Presentation\Config\HabrCareerEnv;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Thesis\Dic;

final class AppModuleTest extends TestCase
{
    /**
     * @throws Throwable
     */
    #[Test]
    #[TestDox('Собирает все пользовательские консольные команды')]
    public function itBuildsConsoleCommands(): void
    {
        $commands = Dic::run(
            new AppModule(
                static fn(): PostgresEnv => new PostgresEnv('postgres', 5432, 'test', 'test', 'test-password'),
                static fn(): HttpServerEnv => new HttpServerEnv('127.0.0.1', 8080),
                static fn(): HabrCareerEnv => new HabrCareerEnv('test-cookie'),
            ),
            static function (Application $application): array {
                self::assertFalse($application->isAutoExitEnabled());

                return $application->all();
            },
        );

        self::assertArrayHasKey('migrate', $commands);
        self::assertArrayHasKey('serve:http', $commands);
        self::assertArrayHasKey('daemon:vacancy-discovery', $commands);
    }
}
