<?php

declare(strict_types=1);

use App\AppModule;
use App\Migration\Presentation\Console\MigrateCommand;
use App\Platform\Postgres\Presentation\Config\PostgresConfig;
use Thesis\Dic;

require dirname(__DIR__) . '/vendor/autoload.php';

exit(Dic::run(
    module: new AppModule(PostgresConfig::fromEnvironment()),
    main: static fn (MigrateCommand $command): int => $command->execute(),
));
