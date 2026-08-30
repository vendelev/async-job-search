<?php

declare(strict_types=1);

namespace Tests\Suite\VacancyDiscovery\Infrastructure\HabrCareer;

use App\VacancyDiscovery\Infrastructure\HabrCareer\HabrCareerVacancyParser;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class HabrCareerVacancyParserTest extends TestCase
{
    #[Test]
    #[TestDox('Извлекает данные вакансии из карточки первой страницы')]
    public function itParsesVacancyCard(): void
    {
        $vacancies = new HabrCareerVacancyParser()->parse(<<<'HTML'
            <html><body><div data-vacancy-list>
              <div class="vacancy-card">
                <div class="vacancy-card__company"><a href="/companies/acme">Acme</a></div>
                <div class="vacancy-card__title">
                  <a class="vacancy-card__title-link" href="/vacancies/42?from=list"> PHP developer </a>
                </div>
                <div class="vacancy-card__salary">
                  <div class="basic-salary">от 200 000 ₽</div>
                </div>
                <div class="vacancy-card__meta">
                  <div class="basic-chip"><svg><use xlink:href="#placemark" /></svg>
                    <div class="chip-with-icon__text">Москва</div>
                  </div>
                  <div class="basic-chip"><svg><use xlink:href="#placemark" /></svg>
                    <div class="chip-with-icon__text">Удалённо</div>
                  </div>
                </div>
                <div class="vacancy-card__skills">
                  <a class="vacancy-card__skills-chip">PHP</a>
                  <a class="vacancy-card__skills-chip">PostgreSQL</a>
                </div>
                <time datetime="2026-08-27T10:20:23+03:00">27 августа</time>
              </div>
            </div></body></html>
            HTML
        );

        self::assertCount(1, $vacancies);
        self::assertSame('habr-career', $vacancies[0]->source);
        self::assertSame('42', $vacancies[0]->externalVacancyId);
        self::assertSame('PHP developer', $vacancies[0]->title);
        self::assertSame('https://career.habr.com/vacancies/42?from=list', $vacancies[0]->url);
        self::assertSame('Acme', $vacancies[0]->employerName);
        self::assertSame('Москва, Удалённо', $vacancies[0]->location);
        self::assertSame([
            'publishedAt' => '2026-08-27T10:20:23+03:00',
            'salary' => 'от 200 000 ₽',
            'skills' => ['PHP', 'PostgreSQL'],
        ], $vacancies[0]->details);
    }

    #[Test]
    #[TestDox('Возвращает пустой список, когда первая страница не содержит вакансий')]
    public function itReturnsEmptyListWhenThereAreNoVacancies(): void
    {
        self::assertSame([], new HabrCareerVacancyParser()->parse('<div data-vacancy-list></div>'));
    }

    #[Test]
    #[TestDox('Нормализует отсутствующее время публикации в null')]
    public function itNormalizesMissingPublicationTimeToNull(): void
    {
        $vacancies = new HabrCareerVacancyParser()->parse(<<<'HTML'
            <div data-vacancy-list>
              <div class="vacancy-card">
                <a class="vacancy-card__title-link" href="/vacancies/42">PHP developer</a>
              </div>
            </div>
            HTML
        );

        self::assertNull($vacancies[0]->details['publishedAt']);
    }

    #[Test]
    #[TestDox('Отклоняет ответ без списка вакансий')]
    public function itRejectsAnUnexpectedPage(): void
    {
        $this->expectException(RuntimeException::class);

        new HabrCareerVacancyParser()->parse('<html><body>Access denied</body></html>');
    }
}
