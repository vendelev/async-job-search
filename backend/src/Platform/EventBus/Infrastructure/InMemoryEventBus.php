<?php

declare(strict_types=1);

namespace App\Platform\EventBus\Infrastructure;

use App\Platform\EventBus\Domain\DomainEvent;
use App\Platform\EventBus\Domain\EventBus;
use App\Platform\EventBus\Domain\EventSubscriber;
use App\Platform\EventStore\Domain\EventStore;
use App\Platform\EventStore\Domain\StoredEvent;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Throwable;

use function Amp\async;

final readonly class InMemoryEventBus implements EventBus
{
    /**
     * @param list<EventSubscriber> $subscribers
     */
    public function __construct(
        private EventStore $eventStore,
        private ClockInterface $clock,
        private array $subscribers,
        private LoggerInterface $logger = new NullLogger(),
    ) {
    }

    /**
     * Сохраняет событие до запуска обработчиков. Ошибка одного обработчика
     * логируется в его Future и не влияет на остальных подписчиков.
     */
    public function publish(DomainEvent $event): void
    {
        $this->eventStore->append(new StoredEvent(
            streamName: $event->streamName(),
            type: $event::class,
            occurredAt: $this->clock->now(),
            payload: $event->payload(),
        ));

        foreach ($this->subscribers as $subscriber) {
            $eventClass = $subscriber->subscribedTo();

            if (!$event instanceof $eventClass) {
                continue;
            }

            async($subscriber->handle(...), $event)
                ->catch(function (Throwable $exception) use ($event, $subscriber): void {
                    $this->logger->error('Не удалось обработать Domain-событие.', [
                        'event_type' => $event::class,
                        'subscriber' => $subscriber::class,
                        'exception' => $exception,
                    ]);
                })
                ->ignore();
        }
    }
}
