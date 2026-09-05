<?php

declare(strict_types=1);

namespace App\VacancyCatalog\Presentation\Config;

use App\Platform\WebServer\Domain\RouteRegister;
use App\Platform\WebServer\Presentation\Config\HttpRouteTag;
use App\Platform\Postgres\Domain\PostgresDatabase;
use App\VacancyCatalog\Application\UseCase\GetVacancy;
use App\VacancyCatalog\Application\UseCase\ListVacancies;
use App\VacancyCatalog\Domain\VacancyCatalog;
use App\VacancyCatalog\Infrastructure\PostgresVacancyCatalog;
use App\VacancyCatalog\Presentation\Http\Controller\GetVacancyController;
use App\VacancyCatalog\Presentation\Http\Controller\ListVacanciesController;
use App\VacancyCatalog\Presentation\Http\View\VacancyCatalogView;
use App\VacancyCatalog\Presentation\Http\View\VacancyCatalogViewFormatter;
use App\VacancyCatalog\Presentation\Http\View\VacancyCatalogTemplateRenderer;
use Thesis\Dic;
use Thesis\Dic\Module;
use Thesis\Dic\Ref;

use function Typhoon\Type\objectT;

/**
 * @implements Module<Ref<RouteRegister>>
 */
final readonly class VacancyCatalogHttpDi implements Module
{
    /**
     * @param Ref<PostgresDatabase> $database
     */
    public function __construct(
        private Ref $database,
    ) {
    }

    /**
     * Регистрирует HTTP-контроллеры и маршруты каталога вакансий.
     *
     * @return Ref<RouteRegister>
     */
    public function configure(Dic $dic): Ref
    {
        $dic
            ->object(PostgresVacancyCatalog::class)
            ->arg('database', $this->database)
            ->bind(objectT(VacancyCatalog::class));
        $dic->object(ListVacancies::class)->bind(objectT(ListVacancies::class));
        $dic->object(VacancyCatalogViewFormatter::class)->bind(objectT(VacancyCatalogViewFormatter::class));
        $dic->object(VacancyCatalogTemplateRenderer::class)->bind(objectT(VacancyCatalogTemplateRenderer::class));
        $dic->object(VacancyCatalogView::class)->bind(objectT(VacancyCatalogView::class));
        $dic
            ->object(ListVacanciesController::class)
            ->bind(objectT(ListVacanciesController::class));
        $dic->object(GetVacancy::class)->bind(objectT(GetVacancy::class));
        $dic
            ->object(GetVacancyController::class)
            ->bind(objectT(GetVacancyController::class));

        return $dic
            ->object(VacancyCatalogRoutes::class)
            ->bind(objectT(RouteRegister::class))
            ->tag(new HttpRouteTag());
    }
}
