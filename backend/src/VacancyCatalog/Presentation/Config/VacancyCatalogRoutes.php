<?php

declare(strict_types=1);

namespace App\VacancyCatalog\Presentation\Config;

use Amp\Http\Server\Router;
use App\Platform\WebServer\Domain\RouteRegister;
use App\VacancyCatalog\Presentation\Http\Controller\GetVacancyController;
use App\VacancyCatalog\Presentation\Http\Controller\ListVacanciesController;
use Error;

final readonly class VacancyCatalogRoutes implements RouteRegister
{
    public function __construct(
        private ListVacanciesController $listVacancies,
        private GetVacancyController $getVacancy,
    ) {
    }

    /**
     * Добавляет HTTP-входы чтения каталога вакансий.
     *
     * @throws Error Если маршруты добавляются после запуска сервера
     */
    public function register(Router $router): void
    {
        $router->addRoute('GET', '/vacancies', $this->listVacancies);
        $router->addRoute('GET', '/vacancies/{source}/{externalVacancyId}', $this->getVacancy);
    }
}
