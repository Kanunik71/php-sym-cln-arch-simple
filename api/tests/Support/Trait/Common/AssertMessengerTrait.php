<?php

declare(strict_types=1);

namespace App\Tests\Support\Trait\Common;

use PHPUnit\Framework\Assert;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;

trait AssertMessengerTrait
{
    /**
     * @template T of object
     *
     * @param class-string<T>             $messageClass
     * @param callable(T): bool|null|null $predicate
     *
     * @return list<T>
     */
    protected function assertDispatched(
        string $messageClass,
        int $times = 1,
        ?callable $predicate = null,
        string $transport = 'messenger.transport.async',
        bool $resetTransport = true,
    ): array {
        $matched = $this->findDispatchedMessages($messageClass, $predicate, $transport);

        Assert::assertCount(
            $times,
            $matched,
            sprintf(
                'Expected %d message(s) of type "%s" on transport "%s", found %d.',
                $times,
                $messageClass,
                $transport,
                count($matched),
            ),
        );

        if ($resetTransport) {
            $this->resetMessengerTransport($transport);
        }

        return $matched;
    }

    /**
     * @template T of object
     *
     * @param class-string<T>             $messageClass
     * @param callable(T): bool|null|null $predicate
     *
     * @return list<T>
     */
    protected function findDispatchedMessages(
        string $messageClass,
        ?callable $predicate = null,
        string $transport = 'messenger.transport.async',
    ): array {
        /** @var InMemoryTransport $transportService */
        $transportService = static::getContainer()->get($transport);

        $matched = [];
        foreach ($transportService->get() as $envelope) {
            $message = $envelope->getMessage();
            if (!$message instanceof $messageClass) {
                continue;
            }

            if ($predicate !== null && !$predicate($message)) {
                continue;
            }

            $matched[] = $message;
        }

        return $matched;
    }

    protected function resetMessengerTransport(string $transport = 'messenger.transport.async'): void
    {
        /** @var InMemoryTransport $transportService */
        $transportService = static::getContainer()->get($transport);
        $transportService->reset();
    }
}
