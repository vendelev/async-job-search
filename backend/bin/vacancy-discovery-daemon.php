<?php

declare(strict_types=1);

use App\VacancyDiscoveryDaemonModule;
use App\Platform\Postgres\Presentation\Config\PostgresConfig;
use App\VacancyDiscovery\Presentation\Config\HabrCareerConfig;
use App\VacancyDiscovery\Presentation\Console\DiscoverVacanciesDaemon;
use Thesis\Dic;

require dirname(__DIR__) . '/vendor/autoload.php';

exit(Dic::run(
    module: new VacancyDiscoveryDaemonModule(PostgresConfig::fromEnvironment(), HabrCareerConfig::fromEnvironment()),
    main: static fn(DiscoverVacanciesDaemon $daemon): int => $daemon->run(),
));
