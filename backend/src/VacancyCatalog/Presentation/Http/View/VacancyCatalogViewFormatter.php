<?php

declare(strict_types=1);

namespace App\VacancyCatalog\Presentation\Http\View;

use JsonException;

final class VacancyCatalogViewFormatter
{
    /**
     * Экранирует текст для безопасного вывода в HTML.
     */
    public function text(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
    }

    /**
     * Возвращает ссылку на первоисточник только для безопасного абсолютного HTTP(S) URL.
     */
    public function sourceUrl(string $value): ?string
    {
        $components = parse_url($value);

        if (!is_array($components)) {
            return null;
        }

        $scheme = $components['scheme'] ?? null;
        $host = $components['host'] ?? null;

        if (
            !is_string($scheme)
            || !is_string($host)
            || $host === ''
            || !in_array(strtolower($scheme), ['http', 'https'], true)
        ) {
            return null;
        }

        return $value;
    }

    /**
     * Формирует URL карточки вакансии.
     */
    public function vacancyPath(string $source, string $externalVacancyId): string
    {
        return '/vacancies/' . rawurlencode($source) . '/' . rawurlencode($externalVacancyId);
    }

    /**
     * Возвращает текстовое значение или значение по умолчанию.
     */
    public function nullableText(?string $value, string $fallback = 'Не указано'): string
    {
        return $value ?? $fallback;
    }

    /**
     * Преобразует дополнительное значение вакансии в текст.
     *
     * @throws JsonException Если дополнительные сведения нельзя представить в JSON
     */
    public function detailValue(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }

        if ($value === null) {
            return 'null';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return json_encode(
            $value,
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE,
        );
    }
}
