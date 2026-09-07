<?php

declare(strict_types=1);

namespace App\Platform\WebServer\Presentation\Config;

use Amp\Http\Server\DefaultErrorHandler;
use Amp\Http\Server\ErrorHandler;
use Amp\Http\Server\Router;
use Amp\Http\Server\SocketHttpServer;
use App\Core\Presentation\Config\ConsoleCommandTag;
use App\Platform\Postgres\Domain\PostgresDatabase;
use App\Platform\WebServer\Presentation\Console\ServerHttp;
use App\VacancyCatalog\Presentation\Config\VacancyCatalogHttpDi;
use Closure;
use Psr\Log\LoggerInterface;
use Thesis\Dic;
use Thesis\Dic\Module;
use Thesis\Dic\Ref;

use function Typhoon\Type\objectT;

/**
 * @implements Module<Ref<ServerHttp>>
 */
final readonly class HttpDi implements Module
{
    /**
     * @param Ref<PostgresDatabase> $database
     * @param Ref<LoggerInterface> $logger
     * @param Closure(): HttpServerEnv $config
     */
    public function __construct(
        private Ref $database,
        private Ref $logger,
        private Closure $config,
    ) {
    }

    /**
     * Регистрирует HTTP-вход приложения.
     *
     * @return Ref<ServerHttp>
     */
    public function configure(Dic $dic): Ref
    {
        $config = $dic->object(HttpServerEnv::class, $this->config);
        $dic->import(new VacancyCatalogHttpDi($this->database));

        $errorHandler = $dic
            ->object(DefaultErrorHandler::class)
            ->bind(objectT(ErrorHandler::class));
        $serverFactory = $dic
            ->object(SocketHttpServerFactory::class)
            ->arg('logger', $this->logger);
        $server = $dic
            ->object(SocketHttpServer::class, [$serverFactory, 'create'])
            ->bind(objectT(SocketHttpServer::class));
        $routeRegistrars = $dic->taggedList(HttpRouteTag::class);
        $routerFactory = $dic
            ->object(RouterFactory::class)
            ->args([
                'logger' => $this->logger,
                'routeRegistrars' => $routeRegistrars,
            ]);
        $router = $dic
            ->object(Router::class, [$routerFactory, 'create'])
            ->bind(objectT(Router::class));
        return $dic
            ->object(ServerHttp::class)
            ->args([
                'server' => $server,
                'router' => $router,
                'errorHandler' => $errorHandler,
                'config' => $config,
            ])
            ->lazy()
            ->tag(new ConsoleCommandTag());
    }
}
