<?php

declare(strict_types=1);

namespace App;

use Amp\Http\Server\DefaultErrorHandler;
use Amp\Http\Server\ErrorHandler;
use Amp\Http\Server\Router;
use Amp\Http\Server\SocketHttpServer;
use App\Platform\WebServer\Presentation\Config\HttpServerEnv;
use App\Platform\WebServer\Presentation\Config\HttpRouteTag;
use App\Platform\WebServer\Presentation\Config\RouterFactory;
use App\Platform\WebServer\Presentation\Config\SocketHttpServerFactory;
use App\Platform\WebServer\Presentation\Console\ServerHttp;
use App\Platform\Logging\Presentation\Config\LoggergDi;
use App\Platform\Postgres\Presentation\Config\PostgresDi;
use App\Platform\Postgres\Presentation\Config\PostgresEnv;
use App\VacancyCatalog\Presentation\Config\VacancyCatalogHttpDi;
use Thesis\Dic;
use Thesis\Dic\Module;
use Thesis\Dic\Ref;

use function Typhoon\Type\objectT;

/**
 * @implements Module<Ref<ServerHttp>>
 */
final readonly class HttpModule implements Module
{
    public function __construct(
        private PostgresEnv $postgresConfig,
        private HttpServerEnv $httpConfig,
    ) {
    }

    /**
     * Собирает HTTP-входы приложения.
     *
     * @return Ref<ServerHttp>
     */
    public function configure(Dic $dic): Ref
    {
        $database = $dic->import(new PostgresDi($this->postgresConfig));
        $logger = $dic->import(new LoggergDi());
        $dic->import(new VacancyCatalogHttpDi($database));

        $dic->object(DefaultErrorHandler::class)->bind(objectT(ErrorHandler::class));
        $serverFactory = $dic
            ->object(SocketHttpServerFactory::class)
            ->arg('logger', $logger);
        $dic
            ->object(SocketHttpServer::class, [$serverFactory, 'create'])
            ->bind(objectT(SocketHttpServer::class));
        $routeRegistrars = $dic->taggedList(HttpRouteTag::class);
        $routerFactory = $dic
            ->object(RouterFactory::class)
            ->args([
                'logger' => $logger,
                'routeRegistrars' => $routeRegistrars,
            ]);
        $dic
            ->object(Router::class, [$routerFactory, 'create'])
            ->bind(objectT(Router::class));

        return $dic
            ->object(ServerHttp::class)
            ->arg('config', $this->httpConfig);
    }
}
