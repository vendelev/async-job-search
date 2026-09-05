<?php

declare(strict_types=1);

namespace App\Platform\Logging\Presentation\Config;

use Monolog\Handler\ErrorLogHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Thesis\Dic;
use Thesis\Dic\Module;
use Thesis\Dic\Ref;

use function Typhoon\Type\objectT;

/**
 * @implements Module<Ref<LoggerInterface>>
 */
final readonly class LoggergDi implements Module
{
    /**
     * Регистрирует PSR-3 logger приложения.
     *
     * @return Ref<LoggerInterface>
     */
    public function configure(Dic $dic): Ref
    {
        return $dic
            ->object(Logger::class, $this->createLogger(...))
            ->bind(objectT(LoggerInterface::class));
    }

    /**
     * Создаёт logger, выводящий записи в error log процесса.
     */
    private function createLogger(): Logger
    {
        return new Logger('async-job-search', [new ErrorLogHandler()]);
    }
}
