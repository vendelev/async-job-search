<?php

declare(strict_types=1);

namespace App\VacancyDiscovery\Presentation\Config;

use Amp\Http\Client\HttpClient;
use Amp\Http\Client\HttpClientBuilder;
use App\VacancyDiscovery\Domain\VacancySource;
use App\VacancyDiscovery\Infrastructure\HabrCareer\HabrCareerVacancyParser;
use App\VacancyDiscovery\Infrastructure\HabrCareer\HabrCareerVacancySource;
use Thesis\Dic;
use Thesis\Dic\Module;
use Thesis\Dic\Ref;

use function Typhoon\Type\objectT;

/**
 * @implements Module<Ref<VacancySource>>
 */
final readonly class HabrCareerModule implements Module
{
    public function __construct(
        private HabrCareerConfig $config,
    ) {
    }

    /**
     * Регистрирует источник вакансий Habr Career.
     *
     * @return Ref<VacancySource>
     */
    public function configure(Dic $dic): Ref
    {
        $client = $dic
            ->object(HttpClient::class, $this->createHttpClient(...))
            ->doNotAutowire();
        $parser = $dic
            ->object(HabrCareerVacancyParser::class)
            ->doNotAutowire();

        return $dic
            ->object(HabrCareerVacancySource::class)
            ->doNotAutowire()
            ->args([
                'client' => $client,
                'parser' => $parser,
                'cookie' => $this->config->cookie(),
            ])
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
