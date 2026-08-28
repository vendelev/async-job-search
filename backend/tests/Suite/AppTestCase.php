<?php

declare(strict_types=1);

namespace Tests\Suite;

use App\MigrateModule;
use App\Platform\Migration\Presentation\Console\MigrateCommand;
use App\Platform\Postgres\Domain\PostgresDatabase;
use App\Platform\Postgres\Domain\PostgresExecutor;
use App\Platform\Postgres\Infrastructure\AmpPostgresTransaction;
use App\Platform\Postgres\Presentation\Config\PostgresConfig;
use App\Platform\Postgres\Presentation\Config\PostgresModule;
use Closure;
use PHPUnit\Framework\TestCase;
use Thesis\Dic;
use Throwable;

use function Amp\async;

abstract class AppTestCase extends TestCase
{
    protected PostgresDatabase $database;

    private static bool $databaseMigrated = false;

    /**
     * Применяет актуальные миграции к тестовой базе один раз за запуск PHPUnit.
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        if (self::$databaseMigrated) {
            return;
        }

        async(static fn(): int => Dic::run(
            new MigrateModule(self::postgresConfig()),
            static fn(MigrateCommand $command): int => $command->execute(),
        ))->await();

        self::$databaseMigrated = true;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->database = Dic::run(
            new PostgresModule(self::postgresConfig()),
            static fn(PostgresDatabase $database): PostgresDatabase => $database,
        );
    }

    /**
     * @param Closure(PostgresExecutor): void $test
     *
     * @throws Throwable Если callback или PostgreSQL завершились ошибкой
     */
    protected function withinTransaction(Closure $test): void
    {
        async(function () use ($test): void {
            $transaction = $this->database->beginTransaction();

            try {
                $test(new AmpPostgresTransaction($transaction));
            } finally {
                if ($transaction->isActive()) {
                    $transaction->rollback();
                }
            }
        })->await();
    }

    private static function postgresConfig(): PostgresConfig
    {
        return PostgresConfig::fromEnvironment();
    }
}
