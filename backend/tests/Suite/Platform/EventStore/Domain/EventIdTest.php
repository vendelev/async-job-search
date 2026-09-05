<?php

declare(strict_types=1);

namespace Tests\Suite\Platform\EventStore\Domain;

use App\Platform\EventStore\Domain\EventId;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

final class EventIdTest extends TestCase
{
    #[Test]
    #[DataProvider('invalidValues')]
    #[TestDox('Отклоняет идентификатор, не являющийся UUIDv7')]
    public function itRejectsInvalidValue(string $value): void
    {
        $this->expectException(InvalidArgumentException::class);

        EventId::fromString($value);
    }

    #[Test]
    #[TestDox('Принимает корректный UUIDv7')]
    public function itAcceptsUuidV7(): void
    {
        $value = Uuid::uuid7()->toString();

        self::assertSame($value, EventId::fromString($value)->value);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidValues(): iterable
    {
        yield 'не UUID' => ['not-a-uuid'];
        yield 'UUIDv4' => [Uuid::uuid4()->toString()];
    }
}
