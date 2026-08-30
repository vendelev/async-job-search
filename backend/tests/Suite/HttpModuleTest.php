<?php

declare(strict_types=1);

namespace Tests\Suite;

use App\HttpModule;
use App\Platform\WebServer\Presentation\Config\HttpServerEnv;
use App\Platform\WebServer\Presentation\Console\ServerHttp;
use App\Platform\Postgres\Presentation\Config\PostgresEnv;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Thesis\Dic;

final class HttpModuleTest extends AppTestCase
{
    #[Test]
    #[TestDox('Собирает HTTP composition root')]
    public function itBuildsHttpCompositionRoot(): void
    {
        $serverBuilt = false;

        Dic::run(
            new HttpModule(
                new PostgresEnv('postgres', 5432, 'async_job_search_test', 'async_job_search_test', 'test-password'),
                new HttpServerEnv('127.0.0.1', 8080),
            ),
            static function (ServerHttp $server) use (&$serverBuilt): void {
                $serverBuilt = true;
            },
        );

        self::assertTrue($serverBuilt);
    }
}
