<?php

declare(strict_types=1);

use App\HttpModule;
use App\Platform\WebServer\Presentation\Config\HttpServerEnv;
use App\Platform\WebServer\Presentation\Console\ServerHttp;
use App\Platform\Postgres\Presentation\Config\PostgresEnv;
use Thesis\Dic;

require dirname(__DIR__) . '/vendor/autoload.php';

exit(Dic::run(
    module: new HttpModule(PostgresEnv::fromEnvironment(), HttpServerEnv::fromEnvironment()),
    main: static fn(ServerHttp $server): int => $server->run(),
));
