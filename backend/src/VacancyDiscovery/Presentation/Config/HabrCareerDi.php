<?php

declare(strict_types=1);

namespace App\VacancyDiscovery\Presentation\Config;

use Amp\Http\Client\HttpClient;
use Amp\Http\Client\HttpClientBuilder;
use App\VacancyDiscovery\Domain\VacancySource;
use App\VacancyDiscovery\Infrastructure\HabrCareer\HabrCareerVacancyParser;
use App\VacancyDiscovery\Infrastructure\HabrCareer\HabrCareerVacancySource;
use Closure;
use Thesis\Dic;
use Thesis\Dic\Module;
use Thesis\Dic\Ref;

use function Typhoon\Type\objectT;

/**
 * @implements Module<Ref<VacancySource>>
 */
final readonly class HabrCareerDi implements Module
{
    /**
     * @param Closure(): HabrCareerEnv $config
     */
    public function __construct(
        private Closure $config,
    ) {
    }

    /**
     * Регистрирует источник вакансий Habr Career.
     *
     * @return Ref<VacancySource>
     */
    public function configure(Dic $dic): Ref
    {
        $dic
            ->object(HabrCareerEnv::class, $this->config)
            ->bind(objectT(HabrCareerEnv::class));
        $dic
            ->object(HttpClient::class, $this->createHttpClient(...))
            ->bind(objectT(HttpClient::class));
        $dic
            ->object(HabrCareerVacancyParser::class)
            ->bind(objectT(HabrCareerVacancyParser::class));

        $sourceFactory = $dic->object(HabrCareerVacancySourceFactory::class);

        return $dic
            ->object(HabrCareerVacancySource::class, [$sourceFactory, 'create'])
            ->bind(objectT(VacancySource::class))
            ->tag(new VacancySourceTag());
    }

    /**
     * Создаёт общий HTTP-клиент с автоматической распаковкой gzip и deflate.
     */
    private function createHttpClient(): HttpClient
    {
        return HttpClientBuilder::buildDefault();
    }
}
