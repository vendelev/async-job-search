<?php

declare(strict_types=1);

namespace Tests\Suite\Architecture;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

final class ArchitectureRulesSmokeTest extends TestCase
{
    private const string PROBE_DIR = 'src/Probe';

    /**
     * @var array{int, string}|null
     */
    private static ?array $analysis = null;

    public static function setUpBeforeClass(): void
    {
        $root = dirname(__DIR__, 3);

        foreach (self::probeFiles() as $path => $content) {
            $fullPath = sprintf('%s/%s', $root, $path);
            $directory = dirname($fullPath);

            if (!is_dir($directory)) {
                mkdir($directory, 0777, true);
            }

            file_put_contents($fullPath, $content);
        }
    }

    public static function tearDownAfterClass(): void
    {
        $root = dirname(__DIR__, 3);
        $probeRoot = sprintf('%s/%s', $root, self::PROBE_DIR);

        $directories = [];

        foreach (array_keys(self::probeFiles()) as $path) {
            $fullPath = sprintf('%s/%s', $root, $path);

            if (is_file($fullPath)) {
                unlink($fullPath);
            }

            $directories[dirname($fullPath)] = true;
        }

        $directories = array_keys($directories);
        usort(
            $directories,
            static fn (string $a, string $b): int => substr_count($b, '/') <=> substr_count($a, '/'),
        );

        foreach ($directories as $directory) {
            if (is_dir($directory)) {
                rmdir($directory);
            }
        }

        if (is_dir($probeRoot)) {
            rmdir($probeRoot);
        }
    }

    #[Test]
    #[TestDox('Выявляет зависимость Infrastructure от Presentation в корневом namespace')]
    public function itCatchesInfrastructureDependOnPresentationAtLayerRoot(): void
    {
        self::assertStringContainsString(
            'App\Probe\Infrastructure\LeakyProbe should not depend on',
            $this->analysis(),
        );
        self::assertStringContainsString('phpat.testInfrastructureLayer', $this->analysis());
    }

    #[Test]
    #[TestDox('Выявляет зависимость Infrastructure от Presentation во вложенном namespace')]
    public function itCatchesInfrastructureDependOnPresentationInSubNamespace(): void
    {
        self::assertStringContainsString(
            'App\Probe\Infrastructure\Sub\LeakySubProbe should not depend on',
            $this->analysis(),
        );
        self::assertStringContainsString('phpat.testInfrastructureLayer', $this->analysis());
    }

    #[Test]
    #[TestDox('Выявляет зависимость Application от Presentation')]
    public function itCatchesApplicationDependOnPresentation(): void
    {
        self::assertStringContainsString(
            'App\Probe\Application\LeakyApplication should not depend on',
            $this->analysis(),
        );
        self::assertStringContainsString('phpat.testApplicationLayer', $this->analysis());
    }

    #[Test]
    #[TestDox('Выявляет зависимость Domain от Application')]
    public function itCatchesDomainDependOnApplication(): void
    {
        self::assertStringContainsString(
            'App\Probe\Domain\LeakyDomain should not depend on',
            $this->analysis(),
        );
        self::assertStringContainsString('phpat.testDomainLayer', $this->analysis());
    }

    #[Test]
    #[TestDox('Игнорирует namespace с похожим именем слоя')]
    public function itIgnoresLayerLookalikeNamespaces(): void
    {
        self::assertStringNotContainsString(
            'App\Probe\InfrastructureBar\InfrastructureBarCanary should not depend on',
            $this->analysis(),
        );
    }

    private function analysis(): string
    {
        if (self::$analysis !== null) {
            return self::$analysis[1];
        }

        $root = dirname(__DIR__, 3);
        $command = sprintf(
            'cd %s && %s vendor/bin/phpstan analyse %s --no-progress --no-ansi --memory-limit=-1 2>&1',
            escapeshellarg($root),
            escapeshellarg(PHP_BINARY),
            self::PROBE_DIR,
        );

        $lines = [];
        $exitCode = 0;
        exec($command, $lines, $exitCode);

        $output = implode("\n", $lines);
        self::$analysis = [$exitCode, $output];

        if ($exitCode === 0) {
            self::fail(
                'PHPat-правила не сработали на заведомых нарушениях — селекторы или проводка правил сломаны.'
                . PHP_EOL
                . $output,
            );
        }

        return $output;
    }

    /**
     * @return array<string, string>
     */
    private static function probeFiles(): array
    {
        return [
            'src/Probe/Presentation/ProbePresenter.php' => <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace App\Probe\Presentation;

                final readonly class ProbePresenter
                {
                }

                PHP,
            'src/Probe/Application/ProbeCommand.php' => <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace App\Probe\Application;

                final readonly class ProbeCommand
                {
                }

                PHP,
            'src/Probe/Application/LeakyApplication.php' => <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace App\Probe\Application;

                use App\Probe\Presentation\ProbePresenter;

                final readonly class LeakyApplication
                {
                    public function __construct(public ProbePresenter $presenter)
                    {
                    }
                }

                PHP,
            'src/Probe/Domain/LeakyDomain.php' => <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace App\Probe\Domain;

                use App\Probe\Application\ProbeCommand;

                final readonly class LeakyDomain
                {
                    public function __construct(public ProbeCommand $command)
                    {
                    }
                }

                PHP,
            'src/Probe/Infrastructure/LeakyProbe.php' => <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace App\Probe\Infrastructure;

                use App\Probe\Presentation\ProbePresenter;

                final readonly class LeakyProbe
                {
                    public function __construct(public ProbePresenter $presenter)
                    {
                    }
                }

                PHP,
            'src/Probe/Infrastructure/Sub/LeakySubProbe.php' => <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace App\Probe\Infrastructure\Sub;

                use App\Probe\Presentation\ProbePresenter;

                final readonly class LeakySubProbe
                {
                    public function __construct(public ProbePresenter $presenter)
                    {
                    }
                }

                PHP,
            'src/Probe/InfrastructureBar/InfrastructureBarCanary.php' => <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace App\Probe\InfrastructureBar;

                use App\Probe\Presentation\ProbePresenter;

                final readonly class InfrastructureBarCanary
                {
                    public function __construct(public ProbePresenter $presenter)
                    {
                    }
                }

                PHP,
        ];
    }
}
