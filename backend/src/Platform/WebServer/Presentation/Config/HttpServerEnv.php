<?php

declare(strict_types=1);

namespace App\Platform\WebServer\Presentation\Config;

use InvalidArgumentException;

final readonly class HttpServerEnv
{
    /**
     * @throws InvalidArgumentException Если параметры HTTP-сервера не заданы или некорректны
     */
    public static function fromEnvironment(): self
    {
        $port = filter_var(self::environment('HTTP_PORT'), FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 65535],
        ]);

        if ($port === false) {
            throw new InvalidArgumentException('HTTP_PORT должен быть целым числом от 1 до 65535.');
        }

        return new self(self::environment('HTTP_HOST'), $port);
    }

    public function __construct(
        public string $host,
        public int $port,
    ) {
    }

    /**
     * @throws InvalidArgumentException Если обязательная переменная не задана
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
