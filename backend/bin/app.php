<?php

declare(strict_types=1);

use App\AppModule;
use App\Platform\Postgres\Presentation\Config\PostgresEnv;
use App\Platform\WebServer\Presentation\Config\HttpServerEnv;
use App\VacancyDiscovery\Presentation\Config\HabrCareerEnv;
use Symfony\Component\Console\Application;
use Thesis\Dic;

require dirname(__DIR__) . '/vendor/autoload.php';

exit(Dic::run(
    module: new AppModule(
        static fn(): PostgresEnv => PostgresEnv::fromEnvironment(),
        static fn(): HttpServerEnv => HttpServerEnv::fromEnvironment(),
        static fn(): HabrCareerEnv => HabrCareerEnv::fromEnvironment(),
    ),
    main: static fn(Application $application): int => $application->run(),
));
