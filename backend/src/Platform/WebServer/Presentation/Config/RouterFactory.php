<?php

declare(strict_types=1);

namespace App\Platform\WebServer\Presentation\Config;

use Amp\Http\Server\ErrorHandler;
use Amp\Http\Server\Router;
use Amp\Http\Server\SocketHttpServer;
use App\Platform\WebServer\Domain\RouteRegister;
use Psr\Log\LoggerInterface;

final readonly class RouterFactory
{
    /**
     * @param iterable<RouteRegister> $routeRegistrars
     */
    public function __construct(
        private SocketHttpServer $server,
        private LoggerInterface $logger,
        private ErrorHandler $errorHandler,
        private iterable $routeRegistrars,
    ) {
    }

    /**
     * Создаёт Router и подключает HTTP-входы всех модулей.
     */
    public function create(): Router
    {
        $router = new Router($this->server, $this->logger, $this->errorHandler);

        foreach ($this->routeRegistrars as $routeRegistrar) {
            $routeRegistrar->register($router);
        }

        return $router;
    }
}
