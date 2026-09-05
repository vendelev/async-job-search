<?php

declare(strict_types=1);

namespace App\VacancyCatalog\Presentation\Http\Controller;

use Amp\Http\HttpStatus;
use Amp\Http\Server\MissingAttributeError;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use Amp\Http\Server\Router;
use App\VacancyCatalog\Application\UseCase\GetVacancy;
use App\VacancyCatalog\Domain\Dto\Vacancy;
use App\VacancyDiscovery\Domain\ExternalVacancyId;
use App\VacancyCatalog\Presentation\Http\View\VacancyCatalogView;
use JsonException;

final readonly class GetVacancyController implements RequestHandler
{
    public function __construct(
        private GetVacancy $getVacancy,
        private VacancyCatalogView $view,
    ) {
    }

    /**
     * @throws MissingAttributeError Если router не передал path-параметры
     * @throws JsonException Если дополнительные сведения нельзя представить в JSON
     */
    public function handleRequest(Request $request): Response
    {
        /** @var array<string, string> $arguments */
        $arguments = $request->getAttribute(Router::class);
        $source = $arguments['source'] ?? '';
        $externalVacancyId = $arguments['externalVacancyId'] ?? '';

        $vacancy = $this->getVacancy->execute(new ExternalVacancyId($source, $externalVacancyId));

        if (!$vacancy instanceof Vacancy) {
            return new Response(
                HttpStatus::NOT_FOUND,
                ['content-type' => 'text/html; charset=utf-8'],
                $this->view->notFound(),
            );
        }

        return new Response(
            HttpStatus::OK,
            ['content-type' => 'text/html; charset=utf-8'],
            $this->view->vacancy($vacancy),
        );
    }
}
