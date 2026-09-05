<?php

declare(strict_types=1);

use App\VacancyCatalog\Domain\Dto\VacancyListItem;

/** @var list<VacancyListItem> $vacancies */
/** @var \Closure(string): string $text */
/** @var \Closure(?string, string=): string $nullableText */
/** @var \Closure(string, string): string $vacancyPath */

?>
<?php if ($vacancies === []) : ?>
    <main>
        <h1>Вакансии</h1>
        <p>Вакансии не найдены</p>
    </main>
<?php else : ?>
    <main>
        <h1>Вакансии</h1>
        <ul>
            <?php foreach ($vacancies as $vacancy) : ?>
                <li>
                    <article>
                        <?php $path = $vacancyPath($vacancy->source, $vacancy->externalVacancyId); ?>
                        <h2><a href="<?= $text($path) ?>"><?= $text($vacancy->title) ?></a></h2>
                        <p>Работодатель: <?= $text($nullableText($vacancy->employerName)) ?></p>
                        <p>Локация: <?= $text($nullableText($vacancy->location)) ?></p>
                        <p>Зарплата: <?= $text($nullableText($vacancy->salary, 'Зарплата не указана')) ?></p>
                    </article>
                </li>
            <?php endforeach; ?>
        </ul>
    </main>
<?php endif; ?>
