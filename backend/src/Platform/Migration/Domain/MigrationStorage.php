<?php

declare(strict_types=1);

namespace App\Platform\Migration\Domain;

interface MigrationStorage
{
    /**
     * Создаёт журнал миграций, если он ещё отсутствует.
     */
    public function initialize(): void;

    /**
     * Проверяет, зафиксирована ли версия миграции в журнале.
     */
    public function isApplied(Migration $migration): bool;

    /**
     * Атомарно применяет миграцию и фиксирует её версию в журнале.
     */
    public function apply(Migration $migration): void;
}
