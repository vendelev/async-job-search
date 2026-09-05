<?php

declare(strict_types=1);

namespace App\Platform\EventStore\Presentation\Config;

use App\Platform\EventStore\Domain\EventStore;
use App\Platform\EventStore\Infrastructure\PostgresEventStore;
use App\Platform\Postgres\Domain\PostgresDatabase;
use Thesis\Dic;
use Thesis\Dic\Module;
use Thesis\Dic\Ref;

/**
 * @implements Module<Ref<EventStore>>
 */
final readonly class EventStoreDi implements Module
{
    /**
     * @param Ref<PostgresDatabase> $database
     */
    public function __construct(
        private Ref $database,
    ) {
    }

    /**
     * Регистрирует PostgreSQL-хранилище событий.
     *
     * @return Ref<EventStore>
     */
    public function configure(Dic $dic): Ref
    {
        return $dic
            ->object(PostgresEventStore::class)
            ->arg('database', $this->database);
    }
}
