<?php

declare(strict_types=1);

namespace App\VacancyCatalog\Presentation\Http\View;

use JsonException;
use App\VacancyCatalog\Domain\Dto\Vacancy;
use App\VacancyCatalog\Domain\Dto\VacancyListItem;

final readonly class VacancyCatalogView
{
    public function __construct(
        private VacancyCatalogViewFormatter $formatter,
        private VacancyCatalogTemplateRenderer $renderer,
    ) {
    }

    /**
     * @param list<VacancyListItem> $vacancies
     */
    public function list(array $vacancies): string
    {
        return $this->renderer->document('Вакансии', $this->renderer->render('list', [
            'vacancies' => $vacancies,
            'text' => $this->formatter->text(...),
            'nullableText' => $this->formatter->nullableText(...),
            'vacancyPath' => $this->formatter->vacancyPath(...),
        ]));
    }

    /**
     * Формирует HTML-карточку вакансии.
     *
     * @throws JsonException Если дополнительные сведения нельзя представить в JSON
     */
    public function vacancy(Vacancy $vacancy): string
    {
        return $this->renderer->document($vacancy->title, $this->renderer->render('vacancy', [
            'vacancy' => $vacancy,
            'sourceUrl' => $this->formatter->sourceUrl($vacancy->url),
            'text' => $this->formatter->text(...),
            'nullableText' => $this->formatter->nullableText(...),
            'detailValue' => $this->formatter->detailValue(...),
        ]));
    }

    /**
     * Формирует HTML-страницу отсутствующей вакансии.
     */
    public function notFound(): string
    {
        return $this->renderer->document('Вакансия не найдена', $this->renderer->render('not-found'));
    }
}
