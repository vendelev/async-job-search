<?php

declare(strict_types=1);

namespace App\Platform\WebServer\Presentation\Console;

use Amp\Http\Server\ErrorHandler;
use Amp\Http\Server\Router;
use Amp\Http\Server\SocketHttpServer;
use App\Platform\WebServer\Presentation\Config\HttpServerEnv;
use Amp\Socket\SocketException;
use Revolt\EventLoop\UnsupportedFeatureException;

use function Amp\trapSignal;

final readonly class ServerHttp
{
    public function __construct(
        private SocketHttpServer $server,
        private Router $router,
        private ErrorHandler $errorHandler,
        private HttpServerEnv $config,
    ) {
    }

    /**
     * Запускает HTTP-сервер до получения SIGINT или SIGTERM.
     *
     * @throws SocketException Если сервер не может открыть сокет
     * @throws UnsupportedFeatureException Если event loop не поддерживает сигналы
     */
    public function run(): int
    {
        $this->server->expose(sprintf('%s:%d', $this->config->host, $this->config->port));
        $this->server->start($this->router, $this->errorHandler);
        trapSignal([SIGINT, SIGTERM]);
        $this->server->stop();

        return 0;
    }
}
