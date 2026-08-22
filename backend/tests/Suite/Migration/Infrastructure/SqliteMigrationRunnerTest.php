<?php

declare(strict_types=1);

namespace Tests\Suite\Migration\Infrastructure;

use App\Migration\Domain\Migration;
use App\Migration\Domain\MigrationProvider;
use App\Migration\Infrastructure\SqliteMigrationRunner;
use PDO;
use PDOException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class SqliteMigrationRunnerTest extends TestCase
{
    private PDO $pdo;

    private string $databasePath;

    protected function setUp(): void
    {
        $databasePath = tempnam(sys_get_temp_dir(), 'async-job-search-');

        if ($databasePath === false) {
            self::fail('Не удалось создать временный файл базы данных.');
        }

        $this->databasePath = $databasePath;
        $this->pdo = new PDO(
            'sqlite:' . $this->databasePath,
            options: [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
        );
    }

    protected function tearDown(): void
    {
        unset($this->pdo);

        if (file_exists($this->databasePath)) {
            unlink($this->databasePath);
        }
    }

    #[Test]
    #[TestDox('Применяет миграции в порядке версий только один раз')]
    /**
     * @throws Throwable Если миграция не может быть применена
     */
    public function itApplies(): void
    {
        $runner = new SqliteMigrationRunner(
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
     * @throws Throwable Если runner выбрасывает исключение, отличное от ожидаемого
     */
    public function itRejects(): void
    {
        $runner = new SqliteMigrationRunner(
            $this->pdo,
            [
                $this->provider(new Migration('module_001_create_results', 'CREATE TABLE first_results (value INTEGER)')),
                $this->provider(new Migration('module_001_create_results', 'CREATE TABLE second_results (value INTEGER)')),
            ],
        );

        $this->expectException(RuntimeException::class);

        $runner->migrate();
    }

    #[Test]
    #[TestDox('Не записывает миграцию при ошибке SQL')]
    /**
     * @throws Throwable Если runner выбрасывает исключение, отличное от ожидаемого
     */
    public function itFails(): void
    {
        $runner = new SqliteMigrationRunner(
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
        return new readonly class(array_values($migrations)) implements MigrationProvider {
            /**
             * @param list<Migration> $migrations
             */
            public function __construct(
                private array $migrations,
            ) {}

            public function migrations(): iterable
            {
                return $this->migrations;
            }
        };
    }
}
