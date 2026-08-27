<?php

declare(strict_types=1);

namespace Tests\Suite\Platform\EventStore\Infrastructure;

use App\Platform\EventStore\Domain\StoredEvent;
use App\Platform\EventStore\Infrastructure\PostgresEventStore;
use App\Platform\Postgres\Domain\PostgresExecutor;
use DateMalformedStringException;
use DateTimeImmutable;
use JsonException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\Suite\AppTestCase;

final class PostgresEventStoreTest extends AppTestCase
{
    /**
     * @throws DateMalformedStringException Если сохранённое время невозможно разобрать
     * @throws JsonException Если payload невозможно сериализовать или разобрать
     */
    #[Test]
    #[TestDox('Добавляет события и читает историю только запрошенного потока в порядке добавления')]
    public function itAppendsEventsAndReadsOnlyTheRequestedStreamInAppendOrder(): void
    {
        $this->withinTransaction(
            /**
             * @throws \DateMalformedStringException Если сохранённое время невозможно разобрать
             * @throws \JsonException Если payload невозможно сериализовать или разобрать
            */
            function (PostgresExecutor $database): void {
                $store = new PostgresEventStore($database);
                $first = new StoredEvent(
                    'event-store-test',
                    'VacancyDiscovered',
                    new DateTimeImmutable('2026-08-22T12:00:00.123456+00:00'),
                    ['vacancyId' => '1'],
                );
                $second = new StoredEvent(
                    'another-event-store-test',
                    'VacancyDiscovered',
                    new DateTimeImmutable('2026-08-22T12:01:00+00:00'),
                    ['vacancyId' => '2'],
                );
                $third = new StoredEvent(
                    'event-store-test',
                    'VacancyDetailsLoaded',
                    new DateTimeImmutable('2026-08-22T11:00:00+00:00'),
                    ['vacancyId' => '1', 'skills' => ['PHP']],
                );
                $store->append($first);
                $store->append($second);
                $store->append($third);

                $history = $store->history('event-store-test');

                self::assertSame(
                    [$first->id->value, $third->id->value],
                    array_map(static fn (StoredEvent $event): string => $event->id->value, $history),
                );
                self::assertSame(['VacancyDiscovered', 'VacancyDetailsLoaded'], array_column($history, 'type'));
                self::assertEquals(['vacancyId' => '1', 'skills' => ['PHP']], $history[1]->payload);
                self::assertSame(
                    '2026-08-22T12:00:00.123456+00:00',
                    $history[0]->occurredAt->format('Y-m-d\TH:i:s.uP'),
                );
            },
        );
    }
}
