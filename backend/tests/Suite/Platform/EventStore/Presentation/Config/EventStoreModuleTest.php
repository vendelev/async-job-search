<?php

declare(strict_types=1);

namespace Tests\Suite\Platform\EventStore\Presentation\Config;

use App\Platform\EventStore\Domain\EventStore;
use App\Platform\Postgres\Presentation\Config\PostgresConfig;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\Suite\AppTestCase;
use Thesis\Dic;

use function Amp\async;

final class EventStoreModuleTest extends AppTestCase
{
    #[Test]
    #[TestDox('Собирает EventStore, подключённый к PostgreSQL')]
    public function itBuildsEventStoreConnectedToPostgres(): void
    {
        async(static function (): void {
            Dic::run(
                new EventStoreTestModule(PostgresConfig::fromEnvironment()),
                static function (EventStore $store): void {
                    self::assertSame([], $store->history('event-store-module-test-empty-stream'));
                },
            );
        })->await();
    }
}
