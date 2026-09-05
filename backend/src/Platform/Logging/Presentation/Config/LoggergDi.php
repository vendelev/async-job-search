<?php

declare(strict_types=1);

namespace App\Platform\Logging\Presentation\Config;

use Amp\Log\ConsoleFormatter;
use Amp\Log\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Thesis\Dic;
use Thesis\Dic\Module;
use Thesis\Dic\Ref;

use function Amp\ByteStream\getStderr;
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
     * Создаёт logger, пишущий в stderr через неблокирующий поток amphp.
     */
    private function createLogger(): Logger
    {
        $handler = new StreamHandler(getStderr());
        $handler->setFormatter(new ConsoleFormatter());

        return new Logger('async-job-search', [$handler]);
    }
}
