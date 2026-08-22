<?php

declare(strict_types=1);

namespace Tests\Suite\Migration\Infrastructure;

use App\Migration\Domain\Migration;
use App\Migration\Domain\MigrationProvider;
use App\Migration\Infrastructure\PostgresMigrationRunner;
use PDO;
use PDOException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Random\RandomException;
use RuntimeException;
use Tests\Suite\AppTestCase;

final class PostgresMigrationRunnerTest extends AppTestCase
{
    private string $schema;

    protected function usesTransaction(): bool
    {
        return false;
    }

    /**
     * @throws RandomException Если не удалось сгенерировать имя временной схемы
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->schema = 'migration_test_' . bin2hex(random_bytes(8));
        $this->pdo->exec(sprintf('CREATE SCHEMA %s', $this->schema));
        $this->pdo->exec(sprintf('SET search_path TO %s', $this->schema));
    }

    protected function tearDown(): void
    {
        $this->pdo->exec(sprintf('DROP SCHEMA IF EXISTS %s CASCADE', $this->schema));

        parent::tearDown();
    }

    #[Test]
    #[TestDox('Применяет миграции в порядке версий только один раз')]
    /**
     * @throws \Throwable Если миграция не может быть применена
     */
    public function itApplies(): void
    {
        $runner = new PostgresMigrationRunner(
            $this->pdo,
            [$this->provider(
                new Migration('module_002_insert_second', 'INSERT INTO results (value) VALUES (2)'),
                new Migration('module_001_create_results', 'CREATE TABLE results (value INTEGER NOT NULL)'),
            )],
        );

        $runner->migrate();
        $runner->migrate();

        $values = $this->pdo->query('SELECT value FROM results');
        self::assertNotFalse($values);

        self::assertSame([2], $values->fetchAll(PDO::FETCH_COLUMN));

        $versions = $this->pdo->query('SELECT version FROM schema_migrations ORDER BY version');
        self::assertNotFalse($versions);

        self::assertSame(
            ['module_001_create_results', 'module_002_insert_second'],
            $versions->fetchAll(PDO::FETCH_COLUMN),
        );
    }

    #[Test]
    #[TestDox('Отклоняет миграции с повторяющейся версией')]
    /**
     * @throws \Throwable Если runner выбрасывает исключение, отличное от ожидаемого
     */
    public function itRejects(): void
    {
        $runner = new PostgresMigrationRunner(
            $this->pdo,
            [
                $this->provider(
                    new Migration('module_001_create_results', 'CREATE TABLE first_results (value INTEGER)'),
                ),
                $this->provider(
                    new Migration('module_001_create_results', 'CREATE TABLE second_results (value INTEGER)'),
                ),
            ],
        );

        $this->expectException(RuntimeException::class);

        $runner->migrate();
    }

    #[Test]
    #[TestDox('Не записывает миграцию при ошибке SQL')]
    /**
     * @throws \Throwable Если runner выбрасывает исключение, отличное от ожидаемого
     */
    public function itFails(): void
    {
        $runner = new PostgresMigrationRunner(
            $this->pdo,
            [$this->provider(new Migration('module_001_broken_sql', 'CREATE TABLE'))],
        );

        $this->expectException(PDOException::class);

        try {
            $runner->migrate();
        } finally {
            $versions = $this->pdo->query('SELECT version FROM schema_migrations');
            self::assertNotFalse($versions);

            self::assertSame([], $versions->fetchAll(PDO::FETCH_COLUMN));
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
