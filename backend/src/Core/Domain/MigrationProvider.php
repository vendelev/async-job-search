<?php

declare(strict_types=1);

namespace App\Core\Domain;

interface MigrationProvider
{
    /**
     * @return iterable<Migration>
     */
    public function migrations(): iterable;
}
