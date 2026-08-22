<?php

declare(strict_types=1);

namespace Tests\Suite;

use App\AppModule;
use App\Migration\Presentation\Config\MigrationConfig;
use App\Migration\Presentation\Console\MigrateCommand;
use PDO;
use PHPUnit\Framework\TestCase;
use Thesis\Dic;

abstract class AppTestCase extends TestCase
{
    protected PDO $pdo;

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

        Dic::run(
            new AppModule(new MigrationConfig(
                self::environment('DATABASE_DSN'),
                self::environment('DATABASE_USER'),
                self::environment('DATABASE_PASSWORD'),
            )),
            static fn (MigrateCommand $command): int => $command->execute(),
        );

        self::$databaseMigrated = true;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->pdo = new PDO(
            self::environment('DATABASE_DSN'),
            self::environment('DATABASE_USER'),
            self::environment('DATABASE_PASSWORD'),
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
        );

        if ($this->usesTransaction()) {
            $this->pdo->beginTransaction();
        }
    }

    protected function tearDown(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }

        parent::tearDown();
    }

    /**
     * Тесты мигратора управляют транзакциями самостоятельно.
     */
    protected function usesTransaction(): bool
    {
        return true;
    }

    private static function environment(string $name): string
    {
        $value = getenv($name);

        if (!is_string($value) || $value === '') {
            self::fail(sprintf('Для интеграционного теста необходима переменная %s.', $name));
        }

        return $value;
    }
}
