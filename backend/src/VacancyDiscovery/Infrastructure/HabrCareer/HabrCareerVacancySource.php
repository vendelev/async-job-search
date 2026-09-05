<?php

declare(strict_types=1);

namespace App\VacancyDiscovery\Infrastructure\HabrCareer;

use SensitiveParameter;
use Amp\ByteStream\StreamException;
use Amp\Cancellation;
use Amp\Http\Client\HttpException;
use Amp\Http\Client\HttpClient;
use Amp\Http\Client\Request;
use App\VacancyDiscovery\Domain\Dto\ExternalVacancy;
use App\VacancyDiscovery\Domain\VacancySource;
use Error;
use RuntimeException;

final readonly class HabrCareerVacancySource implements VacancySource
{
    private const string URL = 'https://career.habr.com/vacancies?q=php&sort=date&type=all';

    private const float TIMEOUT_SECONDS = 3.0;

    public function __construct(
        private HttpClient $client,
        private HabrCareerVacancyParser $parser,
        #[SensitiveParameter]
        private string $cookie,
    ) {
    }

    /**
     * Возвращает вакансии Habr Career с первой страницы выдачи PHP.
     *
     * @return iterable<ExternalVacancy>
     *
     * @throws RuntimeException Если не удалось получить или обработать ответ Habr Career
     * @throws Error Если заголовок HTTP-запроса имеет недопустимое имя или значение
     */
    public function vacancies(?Cancellation $cancellation = null): iterable
    {
        $request = new Request(self::URL);
        $request->setTcpConnectTimeout(self::TIMEOUT_SECONDS);
        $request->setTlsHandshakeTimeout(self::TIMEOUT_SECONDS);
        $request->setTransferTimeout(self::TIMEOUT_SECONDS);
        $request->setInactivityTimeout(self::TIMEOUT_SECONDS);

        foreach (
            [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
                'Sec-GPC' => '1',
                'Upgrade-Insecure-Requests' => '1',
                'Sec-Fetch-Dest' => 'document',
                'Sec-Fetch-Mode' => 'navigate',
                'Sec-Fetch-Site' => 'none',
                'Sec-Fetch-User' => '?1',
                'Priority' => 'u=0, i',
                'Pragma' => 'no-cache',
                'Cache-Control' => 'no-cache',
                'Cookie' => $this->cookie,
            ] as $name => $value
        ) {
            $request->setHeader($name, $value);
        }

        try {
            $response = $this->client->request($request, $cancellation);

            if ($response->getStatus() < 200 || $response->getStatus() >= 300) {
                throw new RuntimeException(sprintf('Habr Career вернул HTTP-статус %d.', $response->getStatus()));
            }

            return $this->parser->parse($response->getBody()->buffer($cancellation));
        } catch (HttpException | StreamException $exception) {
            throw new RuntimeException(
                'Не удалось получить вакансии Habr Career.',
                $exception->getCode(),
                previous: $exception,
            );
        }
    }
}
