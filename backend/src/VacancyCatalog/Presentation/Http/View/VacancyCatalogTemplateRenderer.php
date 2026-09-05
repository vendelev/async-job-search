<?php

declare(strict_types=1);

namespace App\VacancyCatalog\Presentation\Http\View;

use Throwable;

final readonly class VacancyCatalogTemplateRenderer
{
    public function __construct(
        private VacancyCatalogViewFormatter $formatter,
    ) {
    }

    /**
     * Рендерит PHP-шаблон с переданными переменными.
     *
     * @param array<string, mixed> $variables
     */
    public function render(string $template, array $variables = []): string
    {
        $templatePath = __DIR__ . '/Template/' . $template . '.php';

        return (static function () use ($templatePath, $variables): string {
            extract($variables, EXTR_SKIP);
            ob_start();

            try {
                include $templatePath;

                return (string)ob_get_clean();
            } catch (Throwable $exception) {
                ob_end_clean();

                throw $exception;
            }
        })();
    }

    /**
     * Оборачивает содержимое в HTML-документ.
     */
    public function document(string $title, string $content): string
    {
        return $this->render('layout', [
            'title' => $title,
            'content' => $content,
            'text' => $this->formatter->text(...),
        ]);
    }
}
