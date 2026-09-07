<?php

declare(strict_types=1);

namespace Tests\Suite;

use Throwable;
use App\Platform\Logging\Presentation\Config\LoggergDi;
use App\Platform\Postgres\Presentation\Config\PostgresDi;
use App\Platform\Postgres\Presentation\Config\PostgresEnv;
use App\Platform\WebServer\Presentation\Config\HttpServerEnv;
use App\Platform\WebServer\Presentation\Config\HttpDi;
use App\Platform\WebServer\Presentation\Console\ServerHttp;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use ReflectionObject;
use Thesis\Dic;
use Thesis\Dic\Module;
use Thesis\Dic\Ref;

final class HttpDiTest extends AppTestCase
{
    /**
     * @throws Throwable
     */
    #[Test]
    #[TestDox('Собирает HTTP-контекст')]
    public function itBuildsHttpContext(): void
    {
        $serverBuilt = false;

        Dic::run(
            new readonly class implements Module {
                /**
                 * @return Ref<ServerHttp>
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

                    return $dic->import(new HttpDi(
                        $database,
                        $logger,
                        static fn(): HttpServerEnv => new HttpServerEnv('127.0.0.1', 8080),
                    ));
                }
            },
            static function (ServerHttp $server) use (&$serverBuilt): void {
                // ServerHttp регистрируется как lazy-объект, поэтому граф зависимостей
                // собирается только при инициализации прокси.
                new ReflectionObject($server)->initializeLazyObject($server);
                $serverBuilt = true;
            },
        );

        self::assertTrue($serverBuilt);
    }
}
