<?php

declare(strict_types=1);

namespace App\Platform\WebServer\Domain;

use Amp\Http\Server\Router;

interface RouteRegister
{
    /**
     * Добавляет HTTP-маршруты модуля в Router.
     */
    public function register(Router $router): void;
}
