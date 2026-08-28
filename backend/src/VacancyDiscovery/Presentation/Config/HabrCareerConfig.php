<?php

declare(strict_types=1);

namespace App\VacancyDiscovery\Presentation\Config;

use SensitiveParameter;
use InvalidArgumentException;

final readonly class HabrCareerConfig
{
    /**
     * @throws InvalidArgumentException Если cookie Habr Career не задана
     */
    public static function fromEnvironment(): self
    {
        $cookie = getenv('HABR_CAREER_COOKIE');

        if (!is_string($cookie) || $cookie === '') {
            throw new InvalidArgumentException('Не задана переменная окружения HABR_CAREER_COOKIE.');
        }

        return new self($cookie);
    }

    public function __construct(
        #[SensitiveParameter]
        private string $cookie,
    ) {
    }

    /**
     * Возвращает cookie сессии Habr Career.
     */
    public function cookie(): string
    {
        return $this->cookie;
    }
}
