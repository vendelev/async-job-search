<?php

declare(strict_types=1);

namespace App\VacancyCatalog\Presentation\Http\Controller;

use Amp\Http\HttpStatus;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use App\VacancyCatalog\Application\UseCase\ListVacancies;
use App\VacancyCatalog\Domain\Dto\VacancyListItem;
use JsonException;

final readonly class ListVacanciesController implements RequestHandler
{
    public function __construct(
        private ListVacancies $listVacancies,
    ) {
    }

    /**
     * @throws JsonException Если данные вакансии нельзя закодировать в JSON
     */
    public function handleRequest(Request $request): Response
    {
        $vacancies = array_map(
            static fn(VacancyListItem $vacancy): array => [
                'source' => $vacancy->source,
                'externalVacancyId' => $vacancy->externalVacancyId,
                'title' => $vacancy->title,
                'url' => $vacancy->url,
                'employerName' => $vacancy->employerName,
                'location' => $vacancy->location,
            ],
            $this->listVacancies->execute(),
        );

        return new Response(
            HttpStatus::OK,
            ['content-type' => 'application/json; charset=utf-8'],
            json_encode(['vacancies' => $vacancies], JSON_THROW_ON_ERROR),
        );
    }
}
