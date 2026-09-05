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
use App\VacancyCatalog\Presentation\Http\View\VacancyCatalogView;
use App\VacancyCatalog\Presentation\Http\View\VacancyCatalogViewFormatter;
use App\VacancyCatalog\Presentation\Http\View\VacancyCatalogTemplateRenderer;
use App\VacancyDiscovery\Domain\Dto\ExternalVacancy;
use Error;
use League\Uri\Http;
use PHPUnit\Framework\Attributes\DataProvider;
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
     */
    #[Test]
    #[TestDox('Возвращает HTML-список вакансий со ссылкой на карточку')]
    public function itReturnsVacancyList(): void
    {
        $this->withinTransaction(function (PostgresExecutor $database): void {
            $this->catalog($database)->add(
                new ExternalVacancy(
                    'habr-career',
                    '42',
                    'PHP developer',
                    'https://example.test/42',
                    'Acme',
                    'Remote',
                    details: ['salary' => '200 000 руб.'],
                ),
            );

            $response = $this->router($database)->handleRequest($this->request('/vacancies'));
            $body = $this->body($response);

            self::assertSame(HttpStatus::OK, $response->getStatus());
            self::assertSame('text/html; charset=utf-8', $response->getHeader('content-type'));
            self::assertStringContainsString('<h1>Вакансии</h1>', $body);
            self::assertStringContainsString('<a href="/vacancies/habr-career/42">PHP developer</a>', $body);
            self::assertStringContainsString('Работодатель: Acme', $body);
            self::assertStringContainsString('Локация: Remote', $body);
            self::assertStringContainsString('Зарплата: 200 000 руб.', $body);
        });
    }

    /**
     * @throws BufferException Если тело ответа превышает буфер
     * @throws Error Если router не запущен
     */
    #[Test]
    #[TestDox('Возвращает HTML-карточку вакансии')]
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
            self::assertSame('text/html; charset=utf-8', $response->getHeader('content-type'));
            self::assertStringContainsString('<h1>PHP developer</h1>', $body);
            self::assertStringContainsString('Develop backend services.', $body);
            self::assertStringContainsString('<a href="https://example.test/42">Первоисточник</a>', $body);
            self::assertStringContainsString('<th scope="row">salary</th><td>200000</td>', $body);
            self::assertStringContainsString('<a href="/vacancies">К списку вакансий</a>', $body);
        });
    }

    /**
     * @throws BufferException Если тело ответа превышает буфер
     * @throws Error Если router не запущен
     */
    #[Test]
    #[TestDox('Возвращает HTML-страницу пустого списка вакансий')]
    public function itReturnsEmptyVacancyList(): void
    {
        $this->withinTransaction(function (PostgresExecutor $database): void {
            $response = $this->router($database)->handleRequest($this->request('/vacancies'));

            self::assertSame(HttpStatus::OK, $response->getStatus());
            self::assertSame('text/html; charset=utf-8', $response->getHeader('content-type'));
            self::assertStringContainsString('Вакансии не найдены', $this->body($response));
        });
    }

    /**
     * @throws BufferException Если тело ответа превышает буфер
     * @throws Error Если router не запущен
     */
    #[Test]
    #[TestDox('Показывает отсутствие зарплаты в списке вакансий')]
    public function itShowsMissingSalary(): void
    {
        $this->withinTransaction(function (PostgresExecutor $database): void {
            $this->catalog($database)->add(
                new ExternalVacancy('habr-career', '42', 'PHP developer', 'https://example.test/42'),
            );

            $response = $this->router($database)->handleRequest($this->request('/vacancies'));

            self::assertSame(HttpStatus::OK, $response->getStatus());
            self::assertStringContainsString('Зарплата не указана', $this->body($response));
        });
    }

    /**
     * @throws BufferException Если тело ответа превышает буфер
     * @throws Error Если router не запущен
     */
    #[Test]
    #[TestDox('Экранирует данные вакансии из внешнего источника')]
    public function itEscapesExternalVacancyData(): void
    {
        $this->withinTransaction(function (PostgresExecutor $database): void {
            $this->catalog($database)->add(new ExternalVacancy(
                'habr-career',
                '42',
                '<script>alert(1)</script>',
                'https://example.test/42?title=\"><script>alert(1)</script>',
                details: ['<script>key</script>' => ['<script>value</script>']],
            ));

            $response = $this->router($database)->handleRequest($this->request('/vacancies/habr-career/42'));
            $body = $this->body($response);

            self::assertSame(HttpStatus::OK, $response->getStatus());
            self::assertStringNotContainsString('<script>', $body);
            self::assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $body);
            self::assertStringContainsString('&lt;script&gt;key&lt;/script&gt;', $body);
        });
    }

    /**
     * @throws BufferException Если тело ответа превышает буфер
     * @throws Error Если router не запущен
     */
    #[Test]
    #[DataProvider('unsafeSourceUrlProvider')]
    #[TestDox('Не создаёт ссылку для небезопасного URL первоисточника')]
    public function itDoesNotLinkToUnsafeSourceUrl(string $sourceUrl): void
    {
        $this->withinTransaction(function (PostgresExecutor $database) use ($sourceUrl): void {
            $this->catalog($database)->add(
                new ExternalVacancy('habr-career', '42', 'PHP developer', $sourceUrl),
            );

            $response = $this->router($database)->handleRequest($this->request('/vacancies/habr-career/42'));
            $body = $this->body($response);

            self::assertSame(HttpStatus::OK, $response->getStatus());
            self::assertStringContainsString('Первоисточник недоступен', $body);
            self::assertStringNotContainsString('href="' . $sourceUrl . '"', $body);
            self::assertStringNotContainsString('href="#">Первоисточник</a>', $body);
        });
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function unsafeSourceUrlProvider(): iterable
    {
        yield 'javascript-схема' => ['javascript:alert(1)'];
        yield 'некорректный абсолютный URL' => ['https://'];
    }

    /**
     * @throws BufferException Если тело ответа превышает буфер
     * @throws Error Если router не запущен
     */
    #[Test]
    #[TestDox('Возвращает HTML 404 для отсутствующей вакансии')]
    public function itReturnsNotFoundForMissingVacancy(): void
    {
        $this->withinTransaction(function (PostgresExecutor $database): void {
            $response = $this->router($database)->handleRequest($this->request('/vacancies/habr-career/missing'));
            $body = $this->body($response);

            self::assertSame(HttpStatus::NOT_FOUND, $response->getStatus());
            self::assertSame('text/html; charset=utf-8', $response->getHeader('content-type'));
            self::assertStringContainsString('Вакансия не найдена', $body);
            self::assertStringContainsString('<a href="/vacancies">К списку вакансий</a>', $body);
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
        $formatter = new VacancyCatalogViewFormatter();
        $view = new VacancyCatalogView($formatter, new VacancyCatalogTemplateRenderer($formatter));
        new VacancyCatalogRoutes(
            new ListVacanciesController(new ListVacancies($this->catalog($database)), $view),
            new GetVacancyController(new GetVacancy($this->catalog($database)), $view),
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
     * @throws BufferException Если тело ответа превышает буфер
     */
    private function body(Response $response): string
    {
        return buffer($response->getBody());
    }
}
