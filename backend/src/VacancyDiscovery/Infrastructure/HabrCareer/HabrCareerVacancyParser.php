<?php

declare(strict_types=1);

namespace App\VacancyDiscovery\Infrastructure\HabrCareer;

use App\VacancyDiscovery\Domain\Dto\ExternalVacancy;
use DOMDocument;
use DOMElement;
use DOMNameSpaceNode;
use DOMNode;
use DOMNodeList;
use DOMXPath;
use RuntimeException;

final class HabrCareerVacancyParser
{
    private const string SOURCE = 'habr-career';

    private const string BASE_URL = 'https://career.habr.com';

    /**
     * Извлекает вакансии с первой страницы выдачи Habr Career.
     *
     * @return list<ExternalVacancy>
     *
     * @throws RuntimeException Если ответ не содержит списка вакансий Habr Career
     */
    public function parse(string $html): array
    {
        $document = new DOMDocument();
        $previousUseInternalErrors = libxml_use_internal_errors(true);

        try {
            if (!$document->loadHTML('<?xml encoding="UTF-8">' . $html)) {
                throw new RuntimeException('Не удалось разобрать HTML-ответ Habr Career.');
            }
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousUseInternalErrors);
        }

        $xpath = new DOMXPath($document);

        $vacancyList = $xpath->query('//*[@data-vacancy-list]');

        if ($vacancyList === false || $vacancyList->length === 0) {
            throw new RuntimeException('Ответ Habr Career не содержит списка вакансий.');
        }

        $vacancies = [];
        $vacancyListNode = $vacancyList->item(0);

        if (!$vacancyListNode instanceof DOMNode) {
            throw new RuntimeException('Не удалось найти список вакансий Habr Career.');
        }

        $cards = $xpath->query('.//' . $this->classXpath('vacancy-card'), $vacancyListNode);

        if ($cards === false) {
            throw new RuntimeException('Не удалось найти карточки вакансий Habr Career.');
        }

        foreach ($cards as $card) {
            if (!$card instanceof DOMElement) {
                continue;
            }

            $vacancies[] = $this->parseCard($xpath, $card);
        }

        return $vacancies;
    }

    /**
     * @throws RuntimeException Если карточка вакансии не содержит обязательных данных
     */
    private function parseCard(DOMXPath $xpath, DOMElement $card): ExternalVacancy
    {
        $link = $this->firstElement($xpath, './/' . $this->classXpath('vacancy-card__title-link'), $card);
        $href = $link?->getAttribute('href');
        $title = $this->text($link);

        if (
            $title === null
            || !is_string($href)
            || preg_match('~^/vacancies/(\d+)(?:[?#].*)?$~', $href, $matches) !== 1
        ) {
            throw new RuntimeException('Карточка Habr Career не содержит идентификатор или название вакансии.');
        }

        $employer = $this->firstElement(
            $xpath,
            './/' . $this->classXpath('vacancy-card__company') . '//a',
            $card,
        );
        $locations = $xpath->query(
            './/div[contains(concat(" ", normalize-space(@class), " "), " basic-chip ")]
                [.//*[contains(@*[name() = "href" or name() = "xlink:href"], "#placemark")]]
                //div[contains(concat(" ", normalize-space(@class), " "), " chip-with-icon__text ")]',
            $card,
        );

        $locationTexts = $this->texts($locations);

        $publishedAt = $this->firstElement($xpath, './/time', $card)?->getAttribute('datetime');

        return new ExternalVacancy(
            source: self::SOURCE,
            externalVacancyId: $matches[1],
            title: $title,
            url: self::BASE_URL . $href,
            employerName: $this->text($employer),
            location: $locationTexts === [] ? null : implode(', ', $locationTexts),
            details: [
                'publishedAt' => $publishedAt === '' ? null : $publishedAt,
                'salary' => $this->text($this->firstElement($xpath, './/' . $this->classXpath('basic-salary'), $card)),
                'skills' => $this->texts($xpath->query('.//' . $this->classXpath('vacancy-card__skills-chip'), $card)),
            ],
        );
    }

    private function classXpath(string $class): string
    {
        return sprintf('*[contains(concat(" ", normalize-space(@class), " "), " %s ")]', $class);
    }

    private function firstElement(DOMXPath $xpath, string $expression, DOMElement $context): ?DOMElement
    {
        $elements = $xpath->query($expression, $context);

        if ($elements === false) {
            return null;
        }

        $element = $elements->item(0);

        return $element instanceof DOMElement ? $element : null;
    }

    private function text(?DOMElement $element): ?string
    {
        if (!$element instanceof DOMElement) {
            return null;
        }

        $text = trim(preg_replace('/\s+/u', ' ', $element->textContent) ?? '');

        return $text === '' ? null : $text;
    }

    /**
     * @param DOMNodeList<DOMNode|DOMNameSpaceNode>|false $elements
     *
     * @return list<string>
     */
    private function texts(DOMNodeList|false $elements): array
    {
        if (!$elements instanceof DOMNodeList) {
            return [];
        }

        $texts = [];

        foreach ($elements as $element) {
            if (!$element instanceof DOMElement) {
                continue;
            }

            $text = $this->text($element);

            if ($text !== null) {
                $texts[] = $text;
            }
        }

        return $texts;
    }
}
