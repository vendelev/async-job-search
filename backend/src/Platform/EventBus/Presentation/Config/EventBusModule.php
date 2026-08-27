<?php

declare(strict_types=1);

namespace App\Platform\EventBus\Presentation\Config;

use App\Platform\EventBus\Domain\EventBus;
use App\Platform\EventBus\Domain\EventSubscriber;
use App\Platform\EventBus\Infrastructure\InMemoryEventBus;
use App\Platform\EventStore\Domain\EventStore;
use Psr\Clock\ClockInterface;
use Thesis\Dic;
use Thesis\Dic\Module;
use Thesis\Dic\Ref;
use Thesis\Time\WallClock;

use function Typhoon\Type\objectT;

/**
 * @implements Module<Ref<EventBus>>
 */
final readonly class EventBusModule implements Module
{
    /**
     * @param Ref<EventStore> $eventStore
     * @param list<Ref<EventSubscriber>> $subscribers
     */
    public function __construct(
        private Ref $eventStore,
        private array $subscribers = [],
    ) {
    }

    /**
     * Регистрирует in-memory шину и её подписчиков.
     *
     * @return Ref<EventBus>
     */
    public function configure(Dic $dic): Ref
    {
        $clock = $dic
            ->object(WallClock::class)
            ->doNotAutowire()
            ->bind(objectT(ClockInterface::class));

        return $dic
            ->object(InMemoryEventBus::class)
            ->doNotAutowire()
            ->args([
                'eventStore' => $this->eventStore,
                'clock' => $clock,
                'subscribers' => $this->subscribers,
            ])
            ->bind(objectT(EventBus::class));
    }
}
