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
use JsonException;

final readonly class GetVacancyController implements RequestHandler
{
    public function __construct(
        private GetVacancy $getVacancy,
    ) {
    }

    /**
     * @throws JsonException Если данные вакансии нельзя закодировать в JSON
     * @throws MissingAttributeError Если router не передал path-параметры
     */
    public function handleRequest(Request $request): Response
    {
        /** @var array<string, string> $arguments */
        $arguments = $request->getAttribute(Router::class);
        $source = $arguments['source'] ?? '';
        $externalVacancyId = $arguments['externalVacancyId'] ?? '';

        $vacancy = $this->getVacancy->execute(new ExternalVacancyId($source, $externalVacancyId));

        if (!$vacancy instanceof Vacancy) {
            return $this->json(HttpStatus::NOT_FOUND, ['error' => 'Вакансия не найдена']);
        }

        return $this->json(HttpStatus::OK, ['vacancy' => $this->vacancy($vacancy)]);
    }

    /**
     * @param array<string, mixed> $body
     *
     * @throws JsonException Если тело нельзя закодировать в JSON
     */
    private function json(int $status, array $body): Response
    {
        return new Response(
            $status,
            ['content-type' => 'application/json; charset=utf-8'],
            json_encode($body, JSON_THROW_ON_ERROR),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function vacancy(Vacancy $vacancy): array
    {
        return [
            'source' => $vacancy->source,
            'externalVacancyId' => $vacancy->externalVacancyId,
            'title' => $vacancy->title,
            'url' => $vacancy->url,
            'employerName' => $vacancy->employerName,
            'location' => $vacancy->location,
            'description' => $vacancy->description,
            'details' => $vacancy->details,
        ];
    }
}
