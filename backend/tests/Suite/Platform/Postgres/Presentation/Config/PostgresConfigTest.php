<?php

declare(strict_types=1);

namespace Tests\Suite\Platform\Postgres\Presentation\Config;

use App\Platform\Postgres\Presentation\Config\PostgresEnv;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

final class PostgresConfigTest extends TestCase
{
    /**
     * @var array<string, false|string>
     */
    private array $environment = [];

    protected function setUp(): void
    {
        parent::setUp();

        foreach (self::environmentValues() as $name => $value) {
            $this->environment[$name] = getenv($name);
            putenv(sprintf('%s=%s', $name, $value));
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->environment as $name => $value) {
            if ($value === false) {
                putenv($name);
                continue;
            }

            putenv(sprintf('%s=%s', $name, $value));
        }

        parent::tearDown();
    }

    #[Test]
    #[TestDox('Создаёт конфигурацию из переменных окружения')]
    public function itCreatesFromEnvironment(): void
    {
        $config = PostgresEnv::fromEnvironment();

        self::assertSame('postgres', $config->host);
        self::assertSame(5432, $config->port);
        self::assertSame('async_job_search_test', $config->database);
        self::assertSame('async_job_search_test', $config->user);
        self::assertSame('async_job_search_test', $config->password);
    }

    #[Test]
    #[DataProvider('requiredEnvironmentVariables')]
    #[TestDox('Отклоняет отсутствующую обязательную переменную окружения')]
    public function itRejectsMissingRequiredEnvironmentVariable(string $name): void
    {
        putenv($name);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains(sprintf('Не задана переменная окружения %s.', $name));

        PostgresEnv::fromEnvironment();
    }

    #[Test]
    #[DataProvider('invalidPorts')]
    #[TestDox('Отклоняет недопустимый порт PostgreSQL')]
    public function itRejectsInvalidPort(string $port): void
    {
        putenv('DATABASE_PORT=' . $port);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('DATABASE_PORT должен быть целым числом от 1 до 65535.');

        PostgresEnv::fromEnvironment();
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function requiredEnvironmentVariables(): iterable
    {
        foreach (array_keys(self::environmentValues()) as $name) {
            yield $name => [$name];
        }
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidPorts(): iterable
    {
        yield 'ноль' => ['0'];
        yield 'слишком большой' => ['65536'];
        yield 'не число' => ['invalid'];
    }

    /**
     * @return array<string, string>
     */
    private static function environmentValues(): array
    {
        return [
            'DATABASE_HOST' => 'postgres',
            'DATABASE_PORT' => '5432',
            'POSTGRES_DB' => 'async_job_search_test',
            'POSTGRES_USER' => 'async_job_search_test',
            'POSTGRES_PASSWORD' => 'async_job_search_test',
        ];
    }
}
