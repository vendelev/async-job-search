<?php

declare(strict_types=1);

namespace App\VacancyDiscovery\Presentation\Config;

use Amp\Http\Client\HttpClient;
use App\VacancyDiscovery\Infrastructure\HabrCareer\HabrCareerVacancyParser;
use App\VacancyDiscovery\Infrastructure\HabrCareer\HabrCareerVacancySource;

final readonly class HabrCareerVacancySourceFactory
{
    public function __construct(
        private HttpClient $client,
        private HabrCareerVacancyParser $parser,
        private HabrCareerEnv $config,
    ) {
    }

    /**
     * Создаёт источник вакансий с cookie из конфигурации Habr Career.
     *
     * Фабрика нужна, так как Thesis\Dic поддерживает для object() только factory без параметров,
     * а HabrCareerVacancySource принимает cookie строкой, чтобы не зависеть от Presentation\Config.
     */
    public function create(): HabrCareerVacancySource
    {
        return new HabrCareerVacancySource($this->client, $this->parser, $this->config->cookie());
    }
}
