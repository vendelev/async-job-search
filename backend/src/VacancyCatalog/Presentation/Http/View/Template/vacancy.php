<?php

declare(strict_types=1);

use App\VacancyCatalog\Domain\Dto\Vacancy;

/** @var Vacancy $vacancy */
/** @var ?string $sourceUrl */
/** @var \Closure(string): string $text */
/** @var \Closure(?string, string=): string $nullableText */
/** @var \Closure(mixed): string $detailValue */

?>
<main>
    <a href="/vacancies">К списку вакансий</a>
    <h1><?= $text($vacancy->title) ?></h1>
    <p>Работодатель: <?= $text($nullableText($vacancy->employerName)) ?></p>
    <p>Локация: <?= $text($nullableText($vacancy->location)) ?></p>
    <p>
        <?php if ($sourceUrl === null) : ?>
            Первоисточник недоступен
        <?php else : ?>
            <a href="<?= $text($sourceUrl) ?>">Первоисточник</a>
        <?php endif; ?>
    </p>
    <?php if ($vacancy->description !== null && $vacancy->description !== '') : ?>
        <section>
            <h2>Описание</h2>
            <p><?= $text($vacancy->description) ?></p>
        </section>
    <?php endif; ?>
    <?php if ($vacancy->details !== []) : ?>
        <section>
            <h2>Дополнительные сведения</h2>
            <table>
                <?php foreach ($vacancy->details as $key => $value) : ?>
                    <tr><th scope="row"><?= $text((string) $key) ?></th><td><?= $text($detailValue($value)) ?></td></tr>
                <?php endforeach; ?>
            </table>
        </section>
    <?php endif; ?>
</main>
