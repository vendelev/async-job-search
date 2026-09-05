<?php

declare(strict_types=1);

namespace App\VacancyCatalog\Presentation\Http\Controller;

use Amp\Http\HttpStatus;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use App\VacancyCatalog\Application\UseCase\ListVacancies;
use App\VacancyCatalog\Presentation\Http\View\VacancyCatalogView;

final readonly class ListVacanciesController implements RequestHandler
{
    public function __construct(
        private ListVacancies $listVacancies,
        private VacancyCatalogView $view,
    ) {
    }

    public function handleRequest(Request $request): Response
    {
        return new Response(
            HttpStatus::OK,
            ['content-type' => 'text/html; charset=utf-8'],
            $this->view->list($this->listVacancies->execute()),
        );
    }
}
