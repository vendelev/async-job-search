<?php

declare(strict_types=1);

namespace App\Platform\Postgres\Presentation\Config;

use InvalidArgumentException;

final readonly class PostgresEnv
{
    /**
     * @throws InvalidArgumentException Если обязательная переменная PostgreSQL не задана
     */
    public static function fromEnvironment(): self
    {
        $port = filter_var(self::environment('DATABASE_PORT'), FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 65535],
        ]);

        if ($port === false) {
            throw new InvalidArgumentException('DATABASE_PORT должен быть целым числом от 1 до 65535.');
        }

        return new self(
            self::environment('DATABASE_HOST'),
            $port,
            self::environment('POSTGRES_DB'),
            self::environment('POSTGRES_USER'),
            self::environment('POSTGRES_PASSWORD'),
        );
    }

    public function __construct(
        public string $host,
        public int $port,
        public string $database,
        public string $user,
        public string $password,
    ) {
    }

    /**
     * @throws InvalidArgumentException Если обязательная переменная PostgreSQL не задана
     */
    private static function environment(string $name): string
    {
        $value = getenv($name);

        if (!is_string($value) || $value === '') {
            throw new InvalidArgumentException(sprintf('Не задана переменная окружения %s.', $name));
        }

        return $value;
    }
}
