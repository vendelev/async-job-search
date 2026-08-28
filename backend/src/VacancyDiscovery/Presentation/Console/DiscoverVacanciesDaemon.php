<?php

declare(strict_types=1);

namespace App\VacancyDiscovery\Presentation\Console;

use Amp\CancelledException;
use Amp\SignalCancellation;
use App\VacancyDiscovery\Application\UseCase\DiscoverVacancies;
use Psr\Log\LoggerInterface;
use Throwable;

use function Amp\delay;

final readonly class DiscoverVacanciesDaemon
{
    private const int INTERVAL_SECONDS = 600;

    public function __construct(
        private DiscoverVacancies $discoverVacancies,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Периодически запускает поиск вакансий до получения SIGINT или SIGTERM.
     */
    public function run(): int
    {
        $cancellation = new SignalCancellation([SIGINT, SIGTERM]);

        while (!$cancellation->isRequested()) {
            try {
                $this->discoverVacancies->execute($cancellation);
            } catch (Throwable $exception) {
                $this->logger->error('Поиск вакансий завершился ошибкой.', [
                    'exception' => $exception,
                ]);
            }

            try {
                delay(self::INTERVAL_SECONDS, cancellation: $cancellation);
            } catch (CancelledException) {
                return 0;
            }
        }

        return 0;
    }
}
