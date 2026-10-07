<?php

declare(strict_types=1);

namespace App\Tests;

use App\Application\Model\Common\ArrayableModelInterface;
use App\Application\Model\Http\ErrorResponseModel;
use App\Tests\Support\TestBed;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class DatabaseWebTestCase extends WebTestCase
{
    protected TestBed $bed;

    /**
     * @param array<string, mixed> $options
     * @param array<string, mixed> $server
     */
    protected function createClientAndResetDatabase(array $options = [], array $server = []): KernelBrowser
    {
        $client = static::createClient($options, $server);
        $this->bed = TestBed::create(static::getContainer());
        $this->bed->resetSchema();

        return $client;
    }

    /**
     * @param array<string, mixed>|null $payload
     * @param array<string, mixed>      $server
     */
    protected function requestJson(
        KernelBrowser $client,
        string $method,
        string $uri,
        ?array $payload = null,
        array $server = [],
    ): void {
        $client->request(
            $method,
            $uri,
            server: ['CONTENT_TYPE' => 'application/json', ...$server],
            content: $payload !== null
                ? json_encode($payload, JSON_THROW_ON_ERROR)
                : null,
        );
    }

    /**
     * @param array<string, mixed>|string $payload
     * @param array<string, mixed>        $server
     */
    protected function requestRawJson(
        KernelBrowser $client,
        string $method,
        string $uri,
        array|string $payload,
        array $server = [],
    ): void {
        $content = is_array($payload)
            ? json_encode($payload, JSON_THROW_ON_ERROR)
            : $payload;

        $client->request(
            $method,
            $uri,
            server: ['CONTENT_TYPE' => 'application/json', ...$server],
            content: $content,
        );
    }

    /** @return array<string, string> */
    protected function authorizedServer(string $token): array
    {
        return ['HTTP_AUTHORIZATION' => 'Bearer '.$token];
    }

    /**
     * @template T of ArrayableModelInterface
     *
     * @param class-string<T> $class
     *
     * @return T
     */
    protected function decodeModel(KernelBrowser $client, string $class): ArrayableModelInterface
    {
        return $class::fromArray($this->decodeJsonResponse($client));
    }

    /**
     * @template T of ArrayableModelInterface
     *
     * @param class-string<T> $class
     *
     * @return list<T>
     */
    protected function decodeModelList(KernelBrowser $client, string $class): array
    {
        $items = [];
        foreach ($this->decodeJsonListResponse($client) as $item) {
            $items[] = $class::fromArray($item);
        }

        return $items;
    }

    protected function decodeErrorModel(KernelBrowser $client): ErrorResponseModel
    {
        return ErrorResponseModel::fromArray($this->decodeJsonResponse($client));
    }

    /** @return array<string, mixed> */
    protected function decodeJsonResponse(KernelBrowser $client): array
    {
        $content = $client->getResponse()->getContent();
        self::assertNotFalse($content);

        $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($decoded);

        return $this->stringKeyedMap($decoded);
    }

    /** @return list<array<string, mixed>> */
    protected function decodeJsonListResponse(KernelBrowser $client): array
    {
        $content = $client->getResponse()->getContent();
        self::assertNotFalse($content);

        $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        self::assertIsList($decoded);

        $items = [];
        foreach ($decoded as $item) {
            self::assertIsArray($item);
            $items[] = $this->stringKeyedMap($item);
        }

        return $items;
    }

    /**
     * @param array<int|string, mixed> $data
     *
     * @return array<string, mixed>
     */
    private function stringKeyedMap(array $data): array
    {
        $map = [];
        foreach ($data as $key => $value) {
            self::assertIsString($key);
            $map[$key] = $value;
        }

        return $map;
    }
}
