<?php

declare(strict_types=1);

namespace App\Platform\WebServer\Presentation\Console;

use Amp\Http\Server\ErrorHandler;
use Amp\Http\Server\Router;
use Amp\Http\Server\SocketHttpServer;
use App\Platform\WebServer\Presentation\Config\HttpServerEnv;
use Amp\Socket\SocketException;
use Closure;
use Revolt\EventLoop\UnsupportedFeatureException;
use Symfony\Component\Console\Attribute\AsCommand;

use function Amp\trapSignal;

#[AsCommand(name: 'serve:http', description: 'Запускает HTTP-сервер.')]
final readonly class ServerHttp
{
    /**
     * @param Closure(): SocketHttpServer $server
     * @param Closure(): Router $router
     * @param Closure(): ErrorHandler $errorHandler
     * @param Closure(): HttpServerEnv $config
     */
    public function __construct(
        private Closure $server,
        private Closure $router,
        private Closure $errorHandler,
        private Closure $config,
    ) {
    }

    /**
     * Запускает HTTP-сервер до получения SIGINT или SIGTERM.
     *
     * @throws SocketException Если сервер не может открыть сокет
     * @throws UnsupportedFeatureException Если event loop не поддерживает сигналы
     */
    public function __invoke(): int
    {
        $server = ($this->server)();
        $config = ($this->config)();
        $server->expose(sprintf('%s:%d', $config->host, $config->port));
        $server->start(($this->router)(), ($this->errorHandler)());
        trapSignal([SIGINT, SIGTERM]);
        $server->stop();

        return 0;
    }
}
