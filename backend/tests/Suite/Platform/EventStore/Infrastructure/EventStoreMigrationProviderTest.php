<?php

declare(strict_types=1);

namespace Tests\Suite\Platform\EventStore\Infrastructure;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\Suite\AppTestCase;

use function Amp\async;

final class EventStoreMigrationProviderTest extends AppTestCase
{
    #[Test]
    #[TestDox('Создаёт индекс событий по потоку и позиции')]
    public function itCreatesEventsStreamPositionIndex(): void
    {
        $index = async(fn (): ?array => $this->database->execute(
            'SELECT indexname FROM pg_indexes WHERE schemaname = :schema AND indexname = :index_name',
            ['schema' => 'public', 'index_name' => 'event_store_events_stream_position_index'],
        )->fetchRow())->await();

        self::assertSame(['indexname' => 'event_store_events_stream_position_index'], $index);
    }
}
