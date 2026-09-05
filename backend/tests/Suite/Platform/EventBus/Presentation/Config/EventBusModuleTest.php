<?php

declare(strict_types=1);

namespace Tests\Suite\Platform\EventBus\Presentation\Config;

use App\Platform\EventBus\Domain\EventBus;
use App\Platform\EventBus\Infrastructure\InMemoryEventBus;
use App\Platform\Postgres\Presentation\Config\PostgresEnv;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\Suite\AppTestCase;
use Thesis\Dic;

use function Amp\async;

final class EventBusModuleTest extends AppTestCase
{
    #[Test]
    #[TestDox('Собирает EventBus с EventStore и сохраняет опубликованное событие')]
    public function itBuildsEventBusAndStoresPublishedEvent(): void
    {
        async(static function (): void {
            Dic::run(
                new EventBusTestModule(PostgresEnv::fromEnvironment()),
                static function (EventBus $bus): void {
                    $event = new ModuleTestEvent('event-bus-module-test-' . uniqid('', true));
                    $bus->publish($event);

                    self::assertInstanceOf(InMemoryEventBus::class, $bus);
                },
            );
        })->await();
    }
}
