<?php

declare(strict_types=1);

namespace App\VacancyCatalog\Presentation\Config;

use App\Platform\EventBus\Domain\EventSubscriber;
use App\Platform\Postgres\Domain\PostgresDatabase;
use App\VacancyCatalog\Domain\VacancyCatalog;
use App\VacancyCatalog\Infrastructure\PostgresVacancyCatalog;
use App\VacancyCatalog\Presentation\Listener\VacancyDiscoveredSubscriber;
use Thesis\Dic;
use Thesis\Dic\Module;
use Thesis\Dic\Ref;

use function Typhoon\Type\objectT;

/**
 * @implements Module<Ref<EventSubscriber>>
 */
final readonly class VacancyCatalogEventSubscriberDi implements Module
{
    /**
     * @param Ref<PostgresDatabase> $database
     */
    public function __construct(
        private Ref $database,
    ) {
    }

    /**
     * Регистрирует подписчик каталога на события обнаружения вакансий.
     *
     * @return Ref<EventSubscriber>
     */
    public function configure(Dic $dic): Ref
    {
        $dic
            ->object(PostgresVacancyCatalog::class)
            ->arg('database', $this->database)
            ->bind(objectT(VacancyCatalog::class));

        return $dic
            ->object(VacancyDiscoveredSubscriber::class)
            ->bind(objectT(EventSubscriber::class));
    }
}
