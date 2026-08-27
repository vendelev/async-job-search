<?php

declare(strict_types=1);

namespace App\Platform\EventStore\Infrastructure;

use App\Platform\Migration\Domain\Migration;
use App\Platform\Migration\Domain\MigrationProvider;

final readonly class EventStoreMigrationProvider implements MigrationProvider
{
    /**
     * @return iterable<Migration>
     */
    public function migrations(): iterable
    {
        yield new Migration(
            'event_store_001_create_events',
            <<<'SQL'
                CREATE TABLE event_store_events (
                    id UUID PRIMARY KEY,
                    position BIGINT GENERATED ALWAYS AS IDENTITY UNIQUE,
                    stream_name TEXT NOT NULL,
                    type TEXT NOT NULL,
                    occurred_at TIMESTAMPTZ NOT NULL,
                    payload JSONB NOT NULL
                );

                CREATE INDEX event_store_events_stream_position_index
                    ON event_store_events (stream_name, position);
            SQL,
        );
    }
}
