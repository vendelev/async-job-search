<?php

declare(strict_types=1);

namespace App\VacancyCatalog\Presentation\Config;

use App\Platform\EventBus\Domain\EventSubscriber;
use App\Platform\Postgres\Domain\PostgresDatabase;
use App\VacancyCatalog\Application\UseCase\GetVacancy;
use App\VacancyCatalog\Application\UseCase\ListVacancies;
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
final readonly class VacancyCatalogModule implements Module
{
    /**
     * @param Ref<PostgresDatabase> $database
     */
    public function __construct(
        private Ref $database,
    ) {
    }

    /**
     * Регистрирует PostgreSQL-проекцию, сценарии чтения и подписчик события.
     *
     * @return Ref<EventSubscriber>
     */
    public function configure(Dic $dic): Ref
    {
        $catalog = $dic
            ->object(PostgresVacancyCatalog::class)
            ->doNotAutowire()
            ->arg('database', $this->database)
            ->bind(objectT(VacancyCatalog::class));

        $dic->object(ListVacancies::class)->doNotAutowire()->arg('catalog', $catalog);
        $dic->object(GetVacancy::class)->doNotAutowire()->arg('catalog', $catalog);

        return $dic
            ->object(VacancyDiscoveredSubscriber::class)
            ->doNotAutowire()
            ->arg('catalog', $catalog)
            ->bind(objectT(EventSubscriber::class));
    }
}
