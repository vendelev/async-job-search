<?php

declare(strict_types=1);

namespace App\Platform\Postgres\Presentation\Config;

use Amp\Postgres\PostgresConfig as AmpPostgresConfig;
use Amp\Postgres\PostgresConnectionPool;
use App\Platform\Postgres\Domain\PostgresDatabase;
use App\Platform\Postgres\Domain\PostgresExecutor;
use App\Platform\Postgres\Infrastructure\AmpPostgresDatabase;
use Thesis\Dic;
use Thesis\Dic\Module;
use Thesis\Dic\Ref;

use function Typhoon\Type\objectT;

/**
 * @implements Module<Ref<PostgresDatabase>>
 */
final readonly class PostgresDi implements Module
{
    public function __construct(
        private PostgresEnv $config,
    ) {
    }

    /**
     * Регистрирует общий пул неблокирующих подключений к PostgreSQL.
     *
     * @return Ref<PostgresDatabase>
     */
    public function configure(Dic $dic): Ref
    {
        $dic
            ->object(PostgresConnectionPool::class, $this->createPool(...))
            ->bind(objectT(PostgresConnectionPool::class));

        return $dic
            ->object(AmpPostgresDatabase::class)
            ->bind(objectT(PostgresDatabase::class));
    }

    /**
     * Создаёт общий пул неблокирующих подключений к PostgreSQL.
     * Используется в Thesis\Dic как callable service
     */
    private function createPool(): PostgresConnectionPool
    {
        return new PostgresConnectionPool(new AmpPostgresConfig(
            $this->config->host,
            $this->config->port,
            $this->config->user,
            $this->config->password,
            $this->config->database,
        ));
    }
}
