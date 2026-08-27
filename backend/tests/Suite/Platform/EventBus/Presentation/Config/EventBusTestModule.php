<?php

declare(strict_types=1);

namespace Tests\Suite\Platform\EventBus\Presentation\Config;

use App\Platform\EventBus\Domain\EventBus;
use App\Platform\EventBus\Presentation\Config\EventBusModule;
use App\Platform\EventStore\Presentation\Config\EventStoreModule;
use App\Platform\Postgres\Presentation\Config\PostgresConfig;
use App\Platform\Postgres\Presentation\Config\PostgresModule;
use Thesis\Dic;
use Thesis\Dic\Module;
use Thesis\Dic\Ref;

/**
 * @implements Module<Ref<EventBus>>
 */
final readonly class EventBusTestModule implements Module
{
    public function __construct(
        private PostgresConfig $config,
    ) {
    }

    /**
     * @return Ref<EventBus>
     */
    public function configure(Dic $dic): Ref
    {
        $database = $dic->import(new PostgresModule($this->config));
        $eventStore = $dic->import(new EventStoreModule($database));

        return $dic->import(new EventBusModule($eventStore));
    }
}
