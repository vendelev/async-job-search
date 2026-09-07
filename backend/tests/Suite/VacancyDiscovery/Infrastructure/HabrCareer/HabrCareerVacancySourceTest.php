<?php

declare(strict_types=1);

namespace Tests\Suite\VacancyDiscovery\Infrastructure\HabrCareer;

use Error;
use Amp\Cancellation;
use Amp\CancelledException;
use Amp\DeferredCancellation;
use Amp\Http\Client\DelegateHttpClient;
use Amp\Http\Client\HttpClient;
use Amp\Http\Client\Request;
use Amp\Http\Client\Response;
use Amp\Http\Client\SocketException;
use App\VacancyDiscovery\Infrastructure\HabrCareer\HabrCareerVacancyParser;
use App\VacancyDiscovery\Infrastructure\HabrCareer\HabrCareerVacancySource;
use Closure;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class HabrCareerVacancySourceTest extends TestCase
{
    /** @throws Error */
    #[Test]
    #[TestDox('Отклоняет неуспешный HTTP-статус Habr Career')]
    public function itRejectsAnUnsuccessfulHttpStatus(): void
    {
        $source = $this->source(static fn(Request $request, Cancellation $cancellation): Response => new Response(
            '1.1',
            503,
            null,
            [],
            '',
            $request,
        ));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIsOrContains('HTTP-статус 503');

        $source->vacancies();
    }

    /** @throws Error */
    #[Test]
    #[TestDox('Оборачивает сетевую ошибку и сохраняет исходное исключение')]
    public function itWrapsNetworkErrors(): void
    {
        $source = $this->source(static function (Request $request, Cancellation $cancellation): Response {
            throw new SocketException('Соединение разорвано.');
        });

        try {
            $source->vacancies();
            self::fail('Ожидалась ошибка источника Habr Career.');
        } catch (RuntimeException $exception) {
            self::assertInstanceOf(SocketException::class, $exception->getPrevious());
        }
    }

    /** @throws Error */
    #[Test]
    #[TestDox('Передаёт отмену в HTTP-запрос без оборачивания её в ошибку источника')]
    public function itPassesCancellationToTheHttpRequest(): void
    {
        $deferredCancellation = new DeferredCancellation();
        $deferredCancellation->cancel();

        $source = $this->source(static function (Request $request, Cancellation $cancellation): Response {
            $cancellation->throwIfRequested();

            return new Response('1.1', 200, null, [], '<div data-vacancy-list></div>', $request);
        });

        $this->expectException(CancelledException::class);

        $source->vacancies($deferredCancellation->getCancellation());
    }

    /** @throws Error */
    #[Test]
    #[TestDox('Устанавливает трёхсекундный timeout для всех фаз HTTP-запроса')]
    public function itSetsThreeSecondTimeouts(): void
    {
        $request = null;
        $source = $this->source(static function (
            Request $actualRequest,
            Cancellation $cancellation,
        ) use (&$request): Response {
            $request = $actualRequest;

            return new Response('1.1', 200, null, [], '<div data-vacancy-list></div>', $actualRequest);
        });

        $source->vacancies();

        self::assertInstanceOf(Request::class, $request);
        self::assertSame(3.0, $request->getTcpConnectTimeout());
        self::assertSame(3.0, $request->getTlsHandshakeTimeout());
        self::assertSame(3.0, $request->getTransferTimeout());
        self::assertSame(3.0, $request->getInactivityTimeout());
    }

    /**
     * @param Closure(Request, Cancellation): Response $request
     */
    private function source(Closure $request): HabrCareerVacancySource
    {
        return new HabrCareerVacancySource(
            new HttpClient(new readonly class ($request) implements DelegateHttpClient {
                /**
                 * @param Closure(Request, Cancellation): Response $request
                 */
                public function __construct(
                    private Closure $request,
                ) {
                }

                public function request(Request $request, Cancellation $cancellation): Response
                {
                    return ($this->request)($request, $cancellation);
                }
            }, []),
            new HabrCareerVacancyParser(),
            'test-cookie',
        );
    }
}
