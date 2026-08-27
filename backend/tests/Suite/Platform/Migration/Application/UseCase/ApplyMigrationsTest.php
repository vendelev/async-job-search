<?php

declare(strict_types=1);

namespace Platform\Migration\Application\UseCase;

use Amp\Postgres\PostgresQueryError;
use App\Platform\Migration\Application\UseCase\ApplyMigrations;
use App\Platform\Migration\Domain\Migration;
use App\Platform\Migration\Domain\MigrationProvider;
use App\Platform\Migration\Infrastructure\PostgresMigrationStorage;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Random\RandomException;
use RuntimeException;
use Tests\Suite\AppTestCase;
use Throwable;

use function Amp\async;

final class ApplyMigrationsTest extends AppTestCase
{
    /**
     * @throws RandomException Если не удалось сгенерировать уникальные имена
     */
    #[Test]
    #[TestDox('Применяет миграции в порядке версий только один раз')]
    public function itApplies(): void
    {
        $suffix = bin2hex(random_bytes(8));
        $table = 'migration_results_' . $suffix;
        $firstVersion = 'migration_' . $suffix . '_001_create_results';
        $secondVersion = 'migration_' . $suffix . '_002_insert_second';

        async(function () use ($table, $firstVersion, $secondVersion): void {
            $applyMigrations = new ApplyMigrations(
                new PostgresMigrationStorage($this->database),
                [$this->provider(
                    new Migration($secondVersion, sprintf('INSERT INTO %s (value) VALUES (2)', $table)),
                    new Migration($firstVersion, sprintf('CREATE TABLE %s (value INTEGER NOT NULL)', $table)),
                )],
            );

            $applyMigrations->execute();
            $applyMigrations->execute();

            $values = iterator_to_array($this->database->execute(sprintf('SELECT value FROM %s', $table)), false);
            self::assertSame([2], array_column($values, 'value'));

            $versions = iterator_to_array(
                $this->database->execute(sprintf(
                    "SELECT version FROM schema_migrations WHERE version IN ('%s', '%s') ORDER BY version",
                    $firstVersion,
                    $secondVersion,
                )),
                false,
            );
            self::assertSame([$firstVersion, $secondVersion], array_column($versions, 'version'));

            $this->database->execute(sprintf('DROP TABLE %s', $table));
            $this->database->execute(
                'DELETE FROM schema_migrations WHERE version IN (:first, :second)',
                ['first' => $firstVersion, 'second' => $secondVersion],
            );
        })->await();
    }

    #[Test]
    #[TestDox('Отклоняет миграции с повторяющейся версией')]
    public function itRejects(): void
    {
        $applyMigrations = new ApplyMigrations(
            new PostgresMigrationStorage($this->database),
            [
                $this->provider(new Migration('duplicate_001_create', 'CREATE TABLE first_results (value INTEGER)')),
                $this->provider(new Migration('duplicate_001_create', 'CREATE TABLE second_results (value INTEGER)')),
            ],
        );

        $this->expectException(RuntimeException::class);

        async(static function () use ($applyMigrations): void {
            $applyMigrations->execute();
        })->await();
    }

    /**
     * @throws RandomException Если не удалось сгенерировать уникальную версию
     * @throws Throwable Если запрос к PostgreSQL завершился неожиданной ошибкой
     */
    #[Test]
    #[TestDox('Не записывает миграцию при ошибке SQL')]
    public function itFails(): void
    {
        $version = 'migration_' . bin2hex(random_bytes(8)) . '_broken_sql';
        $applyMigrations = new ApplyMigrations(
            new PostgresMigrationStorage($this->database),
            [$this->provider(new Migration($version, 'CREATE TABLE'))],
        );

        try {
            async(static function () use ($applyMigrations): void {
                $applyMigrations->execute();
            })->await();
            self::fail('Ожидалась ошибка SQL.');
        } catch (PostgresQueryError) {
            $result = async(fn (): ?array => $this->database->execute(
                'SELECT 1 FROM schema_migrations WHERE version = :version',
                ['version' => $version],
            )->fetchRow())->await();

            self::assertNull($result);
        }
    }

    private function provider(Migration ...$migrations): MigrationProvider
    {
        return new readonly class (array_values($migrations)) implements MigrationProvider {
            /**
             * @param list<Migration> $migrations
             */
            public function __construct(
                private array $migrations,
            ) {
            }

            public function migrations(): iterable
            {
                return $this->migrations;
            }
        };
    }
}
