<?php

declare(strict_types=1);

namespace Tests\Suite;

use App\Platform\EventStore\Presentation\Config\EventStoreMigrationDi;
use App\Platform\Migration\Presentation\Config\MigrationDi;
use App\Platform\Migration\Presentation\Console\MigrateCommand;
use App\Platform\Postgres\Domain\PostgresDatabase;
use App\Platform\Postgres\Domain\PostgresExecutor;
use App\Platform\Postgres\Infrastructure\AmpPostgresTransaction;
use App\Platform\Postgres\Presentation\Config\PostgresEnv;
use App\Platform\Postgres\Presentation\Config\PostgresDi;
use App\VacancyCatalog\Presentation\Config\VacancyCatalogMigrationDi;
use App\VacancyDiscovery\Presentation\Config\VacancyDiscoveryMigrationDi;
use Closure;
use PHPUnit\Framework\TestCase;
use Thesis\Dic;
use Thesis\Dic\Module;
use Thesis\Dic\Ref;
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
            new readonly class implements Module {
                /**
                 * @return Ref<MigrateCommand>
                 */
                public function configure(Dic $dic): Ref
                {
                    $database = $dic->import(new PostgresDi(
                        static fn(): PostgresEnv => PostgresEnv::fromEnvironment(),
                    ));
                    $migrate = $dic->import(new MigrationDi($database));
                    $dic->import(new EventStoreMigrationDi());
                    $dic->import(new VacancyCatalogMigrationDi());
                    $dic->import(new VacancyDiscoveryMigrationDi());

                    return $migrate;
                }
            },
            static fn(MigrateCommand $command): int => $command(),
        ))->await();

        self::$databaseMigrated = true;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->database = Dic::run(
            new PostgresDi(static fn(): PostgresEnv => self::postgresConfig()),
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

    private static function postgresConfig(): PostgresEnv
    {
        return PostgresEnv::fromEnvironment();
    }
}
