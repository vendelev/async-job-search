<?php

declare(strict_types=1);

namespace Tests\Suite\Platform\EventBus\Infrastructure;

use Amp\DeferredFuture;
use App\Platform\EventBus\Domain\DomainEvent;
use App\Platform\EventBus\Domain\EventSubscriber;
use App\Platform\EventBus\Infrastructure\InMemoryEventBus;
use App\Platform\EventStore\Domain\EventStore;
use App\Platform\EventStore\Domain\StoredEvent;
use DateTimeImmutable;
use DateTimeZone;
use Closure;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;
use RuntimeException;

final class InMemoryEventBusTest extends TestCase
{
    #[Test]
    #[TestDox('Сохраняет событие до запуска его подписчика')]
    public function itStoresEventBeforeStartingSubscribers(): void
    {
        $store = $this->eventStore();
        $handled = new DeferredFuture();
        $event = new TestEvent('vacancy:42', ['vacancy_id' => 42]);
        $bus = new InMemoryEventBus(
            $store,
            $this->clock(),
            [$this->subscriber(static function (DomainEvent $handledEvent) use ($handled, $store): void {
                $handled->complete([$handledEvent, $store->events]);
            })],
        );

        $bus->publish($event);

        [$handledEvent, $storedEvents] = $handled->getFuture()->await();

        self::assertSame($event, $handledEvent);
        self::assertCount(1, $storedEvents);
        self::assertSame('vacancy:42', $storedEvents[0]->streamName);
        self::assertSame(TestEvent::class, $storedEvents[0]->type);
        self::assertSame(['vacancy_id' => 42], $storedEvents[0]->payload);
        self::assertSame('2026-08-27T10:00:00+00:00', $storedEvents[0]->occurredAt->format(DATE_ATOM));
    }

    #[Test]
    #[TestDox('Запускает подходящих подписчиков независимо друг от друга')]
    public function itRunsMatchingSubscribersIndependently(): void
    {
        $handled = new DeferredFuture();
        $bus = new InMemoryEventBus(
            $this->eventStore(),
            $this->clock(),
            [
                $this->subscriber(static function (): never {
                    throw new RuntimeException('Ошибка одного подписчика.');
                }),
                $this->subscriber(static function (DomainEvent $event) use ($handled): void {
                    $handled->complete($event);
                }),
            ],
        );
        $event = new TestEvent('vacancy:42', ['vacancy_id' => 42]);

        $bus->publish($event);

        self::assertSame($event, $handled->getFuture()->await());
    }

    #[Test]
    #[TestDox('Не запускает подписчиков, когда EventStore не сохранил событие')]
    public function itDoesNotStartSubscribersWhenStoringEventFails(): void
    {
        $handled = false;
        $store = new class implements EventStore {
            public function append(StoredEvent $event): void
            {
                throw new RuntimeException('PostgreSQL недоступен.');
            }

            public function history(string $streamName): array
            {
                return [];
            }
        };
        $bus = new InMemoryEventBus(
            $store,
            $this->clock(),
            [$this->subscriber(static function () use (&$handled): void {
                $handled = true;
            })],
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIsOrContains('PostgreSQL недоступен.');

        try {
            $bus->publish(new TestEvent('vacancy:42', ['vacancy_id' => 42]));
        } finally {
            self::assertFalse($handled);
        }
    }

    /**
     * @return object{events: list<StoredEvent>}&EventStore
     */
    private function eventStore(): object
    {
        return new class implements EventStore {
            /**
             * @var list<StoredEvent>
             */
            public array $events = [];

            public function append(StoredEvent $event): void
            {
                $this->events[] = $event;
            }

            public function history(string $streamName): array
            {
                return array_values(array_filter(
                    $this->events,
                    static fn(StoredEvent $event): bool => $event->streamName === $streamName,
                ));
            }
        };
    }

    private function clock(): ClockInterface
    {
        return new class implements ClockInterface {
            public function now(): DateTimeImmutable
            {
                return new DateTimeImmutable('2026-08-27 10:00:00', new DateTimeZone('UTC'));
            }
        };
    }

    private function subscriber(Closure $handler): EventSubscriber
    {
        return new readonly class ($handler) implements EventSubscriber {
            public function __construct(
                private Closure $handler,
            ) {
            }

            public function subscribedTo(): string
            {
                return TestEvent::class;
            }

            public function handle(DomainEvent $event): void
            {
                ($this->handler)($event);
            }
        };
    }
}
