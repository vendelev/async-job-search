<?php

declare(strict_types=1);

namespace App\Platform\WebServer\Presentation\Config;

use Amp\Http\Server\SocketHttpServer;
use Psr\Log\LoggerInterface;

final readonly class SocketHttpServerFactory
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Создаёт HTTP-сервер, доступный напрямую.
     *
     * Фабрика нужна, так как Thesis\Dic поддерживает для object() только factory без параметров,
     * а SocketHttpServer::createForDirectAccess() требует LoggerInterface.
     */
    public function create(): SocketHttpServer
    {
        return SocketHttpServer::createForDirectAccess($this->logger);
    }
}
