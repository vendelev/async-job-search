<?php

declare(strict_types=1);

namespace Tests\Suite\Platform\EventStore\Presentation\Config;

use App\Platform\EventStore\Domain\EventStore;
use App\Platform\EventStore\Presentation\Config\EventStoreModule;
use App\Platform\Postgres\Presentation\Config\PostgresConfig;
use App\Platform\Postgres\Presentation\Config\PostgresModule;
use Thesis\Dic;
use Thesis\Dic\Module;
use Thesis\Dic\Ref;

/**
 * Тестовый модуль: воспроизводит проводку, которую выполнит `AppModule`
 * при появлении runtime-потребителя `EventStore`.
 *
 * @implements Module<Ref<EventStore>>
 */
final readonly class EventStoreTestModule implements Module
{
    public function __construct(
        private PostgresConfig $config,
    ) {
    }

    /**
     * @return Ref<EventStore>
     */
    public function configure(Dic $dic): Ref
    {
        $database = $dic->import(new PostgresModule($this->config));

        return $dic->import(new EventStoreModule($database));
    }
}
