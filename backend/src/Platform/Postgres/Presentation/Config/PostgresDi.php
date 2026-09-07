<?php

declare(strict_types=1);

namespace App\Platform\Postgres\Presentation\Config;

use Amp\Postgres\PostgresConfig as AmpPostgresConfig;
use Amp\Postgres\PostgresConnectionPool;
use App\Platform\Postgres\Domain\PostgresDatabase;
use App\Platform\Postgres\Infrastructure\AmpPostgresDatabase;
use Closure;
use Thesis\Dic;
use Thesis\Dic\Module;
use Thesis\Dic\Ref;

use function Typhoon\Type\objectT;

/**
 * @implements Module<Ref<PostgresDatabase>>
 */
final readonly class PostgresDi implements Module
{
    /**
     * @param Closure(): PostgresEnv $config
     */
    public function __construct(
        private Closure $config,
    ) {
    }

    /**
     * Регистрирует общий пул неблокирующих подключений к PostgreSQL.
     *
     * @return Ref<PostgresDatabase>
     */
    public function configure(Dic $dic): Ref
    {
        $dic->object(PostgresEnv::class, $this->config);
        $dic
            ->object(PostgresConnectionPool::class, fn(): PostgresConnectionPool => $this->createPool())
            ->bind(objectT(PostgresConnectionPool::class));

        return $dic
            ->object(AmpPostgresDatabase::class)
            ->bind(objectT(PostgresDatabase::class));
    }

    /**
     * Создаёт общий пул неблокирующих подключений к PostgreSQL.
     * Используется в Thesis\Dic как callable service.
     *
     */
    private function createPool(): PostgresConnectionPool
    {
        $config = ($this->config)();

        return new PostgresConnectionPool(new AmpPostgresConfig(
            $config->host,
            $config->port,
            $config->user,
            $config->password,
            $config->database,
        ));
    }
}
