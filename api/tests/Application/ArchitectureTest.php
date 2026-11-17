<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Application\Port\TransactionManagerInterface;
use LogicException;
use PHPUnit\Framework\TestCase;

/**
 * Граница входа Application.
 *
 * Use case — это `Action` и `Query`: они вызываются из Controller и из другого
 * Action/Query. Всё остальное в `Application/Service/**` — Service
 * (переиспользуемый шаг): Policy не проверяет, response Model не собирает,
 * транзакцию не открывает и из use case не вызывается. Имена прочих подпапок
 * (`Lifecycle`, `Auth`, `Common`, новые) роли не задают — это навигация.
 *
 * Job'ы (Console, MessageHandler) входят в Service напрямую: у них нет актора,
 * Policy и response Model.
 */
final class ArchitectureTest extends TestCase
{
    private const SOURCE_ROOT = __DIR__.'/../../src';

    private const SERVICE_NAMESPACE = 'App\\Application\\Service\\';

    /** @var array<string, list<string>>|null */
    private static ?array $imports = null;

    public function testScanClassifiesUseCasesAndServices(): void
    {
        $classes = self::classes();

        $useCases = array_filter(array_keys($classes), self::isUseCase(...));
        $services = array_filter(array_keys($classes), self::isService(...));

        self::assertNotEmpty($useCases, 'No use case found: check Action/Query detection.');
        self::assertNotEmpty($services, 'No service found: check Service detection.');
    }

    public function testServiceDoesNotDependOnUseCase(): void
    {
        self::assertNoImports(
            from: self::isService(...),
            to: self::isUseCase(...),
            message: 'Service must not call a use case: orchestration belongs to Action/Query, Service is a reusable step.',
        );
    }

    public function testControllerEntersThroughUseCase(): void
    {
        self::assertNoImports(
            from: self::isController(...),
            to: self::isService(...),
            message: 'Controller must enter through Action/Query: Policy and response model are use-case concerns.',
        );
    }

    public function testJobEntersThroughService(): void
    {
        self::assertNoImports(
            from: self::isJobEntry(...),
            to: self::isUseCase(...),
            message: 'Console/MessageHandler must enter through a Service: a use case has a single entry (HTTP).',
        );
    }

    public function testCollaboratorsDoNotDependOnUseCase(): void
    {
        self::assertNoImports(
            from: self::isCollaborator(...),
            to: self::isUseCase(...),
            message: 'Policy/Mapper/Cache/Dispatcher must not call a use case.',
        );
    }

    public function testServiceDoesNotOpenTransactions(): void
    {
        self::assertNoImports(
            from: self::isService(...),
            to: static fn (string $class): bool => $class === TransactionManagerInterface::class,
            message: 'Transaction boundary belongs to the entry point: Service participates in a transaction, never opens one.',
        );
    }

    /**
     * @param callable(string): bool $from
     * @param callable(string): bool $to
     */
    private static function assertNoImports(callable $from, callable $to, string $message): void
    {
        $violations = [];

        foreach (self::classes() as $class => $imports) {
            if (!$from($class)) {
                continue;
            }

            foreach ($imports as $import) {
                if ($to($import)) {
                    $violations[] = sprintf('%s -> %s', $class, $import);
                }
            }
        }

        self::assertSame([], $violations, $message);
    }

    private static function isUseCase(string $class): bool
    {
        return self::isInServiceNamespace($class)
            && (str_contains($class, '\\Action\\') || str_contains($class, '\\Query\\'));
    }

    private static function isService(string $class): bool
    {
        return self::isInServiceNamespace($class) && !self::isUseCase($class);
    }

    private static function isInServiceNamespace(string $class): bool
    {
        return str_starts_with($class, self::SERVICE_NAMESPACE);
    }

    private static function isController(string $class): bool
    {
        return str_starts_with($class, 'App\\Presentation\\Controller\\');
    }

    private static function isJobEntry(string $class): bool
    {
        return str_starts_with($class, 'App\\Presentation\\Console\\')
            || str_starts_with($class, 'App\\Infrastructure\\Messaging\\');
    }

    private static function isCollaborator(string $class): bool
    {
        foreach (['Policy', 'Mapper', 'Cache', 'Dispatcher'] as $namespace) {
            if (str_starts_with($class, 'App\\Application\\'.$namespace.'\\')) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, list<string>>
     */
    private static function classes(): array
    {
        if (self::$imports !== null) {
            return self::$imports;
        }

        $classes = [];

        foreach (self::phpFiles(self::SOURCE_ROOT) as $path) {
            $contents = file_get_contents($path);

            if ($contents === false) {
                continue;
            }

            $classes[self::classNameOf($path)] = self::importsOf($contents);
        }

        self::$imports = $classes;

        return self::$imports;
    }

    /**
     * @return list<string>
     */
    private static function phpFiles(string $directory): array
    {
        $paths = [];

        foreach (scandir($directory) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $path = $directory.DIRECTORY_SEPARATOR.$entry;

            if (is_dir($path)) {
                $paths = [...$paths, ...self::phpFiles($path)];

                continue;
            }

            if (str_ends_with($entry, '.php')) {
                $paths[] = $path;
            }
        }

        return $paths;
    }

    private static function classNameOf(string $path): string
    {
        $normalized = str_replace('\\', '/', $path);
        $marker = '/src/';
        $position = strpos($normalized, $marker);

        if ($position === false) {
            throw new LogicException(sprintf('Path %s is outside %s.', $path, $marker));
        }

        $relative = substr($normalized, $position + strlen($marker));

        return 'App\\'.str_replace('/', '\\', substr($relative, 0, -strlen('.php')));
    }

    /**
     * @return list<string>
     */
    private static function importsOf(string $contents): array
    {
        $count = preg_match_all(
            '/^use\s+(?!function\s|const\s)([A-Za-z0-9_\\\\]+)(?:\s+as\s+[A-Za-z0-9_]+)?;/m',
            $contents,
            $matches,
        );

        if ($count === false || $count === 0) {
            return [];
        }

        return $matches[1];
    }
}
