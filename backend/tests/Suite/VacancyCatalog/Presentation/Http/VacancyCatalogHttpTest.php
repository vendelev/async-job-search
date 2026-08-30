<?php

declare(strict_types=1);

namespace Tests\Suite\VacancyCatalog\Presentation\Http;

use Amp\ByteStream\BufferException;
use Amp\Http\HttpStatus;
use Amp\Http\Server\DefaultErrorHandler;
use Amp\Http\Server\Driver\Client;
use Amp\Http\Server\HttpServerStatus;
use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Amp\Http\Server\Router;
use Amp\Http\Server\SocketHttpServer;
use Amp\Socket\SocketException;
use App\Platform\Postgres\Domain\PostgresExecutor;
use App\VacancyCatalog\Application\UseCase\GetVacancy;
use App\VacancyCatalog\Application\UseCase\ListVacancies;
use App\VacancyCatalog\Infrastructure\PostgresVacancyCatalog;
use App\VacancyCatalog\Presentation\Config\VacancyCatalogRoutes;
use App\VacancyCatalog\Presentation\Http\Controller\GetVacancyController;
use App\VacancyCatalog\Presentation\Http\Controller\ListVacanciesController;
use App\VacancyDiscovery\Domain\Dto\ExternalVacancy;
use Error;
use JsonException;
use League\Uri\Http;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Psr\Log\NullLogger;
use Tests\Suite\AppTestCase;

use function Amp\ByteStream\buffer;

final class VacancyCatalogHttpTest extends AppTestCase
{
    private ?SocketHttpServer $server = null;

    /**
     * @throws BufferException Если тело ответа превышает буфер
     * @throws Error Если router не запущен
     * @throws JsonException Если ответ не является JSON
     */
    #[Test]
    #[TestDox('Возвращает JSON-список вакансий')]
    public function itReturnsVacancyList(): void
    {
        $this->withinTransaction(function (PostgresExecutor $database): void {
            $this->catalog($database)->add(
                new ExternalVacancy('habr-career', '42', 'PHP developer', 'https://example.test/42', 'Acme', 'Remote'),
            );

            $response = $this->router($database)->handleRequest($this->request('/vacancies'));

            self::assertSame(HttpStatus::OK, $response->getStatus());
            self::assertSame('application/json; charset=utf-8', $response->getHeader('content-type'));
            self::assertSame([
                'vacancies' => [[
                    'source' => 'habr-career',
                    'externalVacancyId' => '42',
                    'title' => 'PHP developer',
                    'url' => 'https://example.test/42',
                    'employerName' => 'Acme',
                    'location' => 'Remote',
                ]],
            ], $this->body($response));
        });
    }

    /**
     * @throws BufferException Если тело ответа превышает буфер
     * @throws Error Если router не запущен
     * @throws JsonException Если ответ не является JSON
     */
    #[Test]
    #[TestDox('Возвращает JSON-карточку вакансии')]
    public function itReturnsVacancyCard(): void
    {
        $this->withinTransaction(function (PostgresExecutor $database): void {
            $this->catalog($database)->add(new ExternalVacancy(
                'habr-career',
                '42',
                'PHP developer',
                'https://example.test/42',
                'Acme',
                'Remote',
                'Develop backend services.',
                ['salary' => '200000'],
            ));

            $response = $this->router($database)->handleRequest($this->request('/vacancies/habr-career/42'));

            $body = $this->body($response);

            self::assertSame(HttpStatus::OK, $response->getStatus());
            self::assertSame('Develop backend services.', $body['vacancy']['description']);
            self::assertSame(['salary' => '200000'], $body['vacancy']['details']);
        });
    }

    /**
     * @throws BufferException Если тело ответа превышает буфер
     * @throws Error Если router не запущен
     * @throws JsonException Если ответ не является JSON
     */
    #[Test]
    #[TestDox('Возвращает 404 для отсутствующей вакансии')]
    public function itReturnsNotFoundForMissingVacancy(): void
    {
        $this->withinTransaction(function (PostgresExecutor $database): void {
            $response = $this->router($database)->handleRequest($this->request('/vacancies/habr-career/missing'));

            self::assertSame(HttpStatus::NOT_FOUND, $response->getStatus());
            self::assertSame(['error' => 'Вакансия не найдена'], $this->body($response));
        });
    }

    /**
     * Создаёт Router production-конфигурации, работающий с переданной PostgreSQL-транзакцией.
     *
     * @throws Error Если HTTP-сервер уже запущен
     * @throws SocketException
     */
    private function router(PostgresExecutor $database): Router
    {
        $logger = new NullLogger();
        $errorHandler = new DefaultErrorHandler();
        $this->server = SocketHttpServer::createForDirectAccess($logger);
        $this->server->expose('127.0.0.1:0');

        $router = new Router($this->server, $logger, $errorHandler);
        new VacancyCatalogRoutes(
            new ListVacanciesController(new ListVacancies($this->catalog($database))),
            new GetVacancyController(new GetVacancy($this->catalog($database))),
        )->register($router);
        $this->server->start($router, $errorHandler);

        return $router;
    }

    private function catalog(PostgresExecutor $database): PostgresVacancyCatalog
    {
        return new PostgresVacancyCatalog($database);
    }

    protected function tearDown(): void
    {
        if ($this->server?->getStatus() === HttpServerStatus::Started) {
            $this->server->stop();
        }

        $this->server = null;

        parent::tearDown();
    }

    private function request(string $path): Request
    {
        return new Request(
            self::createStub(Client::class),
            'GET',
            Http::new('http://localhost' . $path),
        );
    }

    /**
     * @return array<string, mixed>
     *
     * @throws BufferException Если тело ответа превышает буфер
     * @throws JsonException Если тело HTTP-ответа не является JSON
     */
    private function body(Response $response): array
    {
        $body = buffer($response->getBody());

        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);

        return $decoded;
    }
}
