<?php

declare(strict_types=1);

namespace Tests\Suite\Platform\EventStore\Domain;

use App\Platform\EventStore\Domain\StoredEvent;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

final class StoredEventTest extends TestCase
{
    #[Test]
    #[TestDox('Нормализует время события к UTC')]
    public function itNormalizesOccurredAtToUtc(): void
    {
        $event = new StoredEvent(
            'vacancy-1',
            'VacancyDiscovered',
            new DateTimeImmutable('2026-08-22T15:30:00+03:00'),
            ['vacancyId' => '1'],
        );

        self::assertSame('2026-08-22T12:30:00+00:00', $event->occurredAt->format(DATE_ATOM));
        self::assertSame('UTC', $event->occurredAt->getTimezone()->getName());
        self::assertSame('7', $event->id->value[14]);
    }

    #[Test]
    #[DataProvider('emptyRequiredFields')]
    #[TestDox('Отклоняет событие с пустым обязательным полем')]
    public function itRejectsEmptyRequiredFields(string $streamName, string $type): void
    {
        $this->expectException(InvalidArgumentException::class);

        new StoredEvent($streamName, $type, new DateTimeImmutable('2026-08-22T12:00:00+00:00'), ['vacancyId' => '1']);
    }

    #[Test]
    #[TestDox('Отклоняет событие с не сериализуемым payload')]
    public function itRejectsInvalidPayload(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new StoredEvent(
            'vacancy-1',
            'VacancyDiscovered',
            new DateTimeImmutable('2026-08-22T12:00:00+00:00'),
            ['value' => INF],
        );
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function emptyRequiredFields(): iterable
    {
        yield 'пустое имя потока' => ['', 'VacancyDiscovered'];
        yield 'пустой тип события' => ['vacancy-1', ''];
    }
}
