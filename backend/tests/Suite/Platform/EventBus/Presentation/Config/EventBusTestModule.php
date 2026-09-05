<?php

declare(strict_types=1);

namespace Tests\Suite\Platform\EventBus\Presentation\Config;

use App\Platform\EventBus\Domain\EventBus;
use App\Platform\EventBus\Presentation\Config\EventBusDi;
use App\Platform\EventStore\Presentation\Config\EventStoreDi;
use App\Platform\Postgres\Presentation\Config\PostgresEnv;
use App\Platform\Postgres\Presentation\Config\PostgresDi;
use Thesis\Dic;
use Thesis\Dic\Module;
use Thesis\Dic\Ref;

/**
 * @implements Module<Ref<EventBus>>
 */
final readonly class EventBusTestModule implements Module
{
    public function __construct(
        private PostgresEnv $config,
    ) {
    }

    /**
     * @return Ref<EventBus>
     */
    public function configure(Dic $dic): Ref
    {
        $database = $dic->import(new PostgresDi($this->config));
        $eventStore = $dic->import(new EventStoreDi($database));

        return $dic->import(new EventBusDi($eventStore));
    }
}
