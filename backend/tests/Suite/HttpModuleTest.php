<?php

declare(strict_types=1);

namespace Tests\Suite;

use App\Platform\Postgres\Presentation\Config\PostgresDi;
use App\Platform\WebServer\Presentation\Config\HttpServerEnv;
use App\Platform\WebServer\Presentation\Config\HttpDi;
use App\Platform\WebServer\Presentation\Console\ServerHttp;
use App\Platform\Postgres\Presentation\Config\PostgresEnv;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Thesis\Dic;
use Thesis\Dic\Module;
use Thesis\Dic\Ref;

final class HttpModuleTest extends AppTestCase
{
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

                    return $dic->import(new HttpDi(
                        $database,
                        static fn(): HttpServerEnv => new HttpServerEnv('127.0.0.1', 8080),
                    ));
                }
            },
            static function (ServerHttp $server) use (&$serverBuilt): void {
                $serverBuilt = true;
            },
        );

        self::assertTrue($serverBuilt);
    }
}
