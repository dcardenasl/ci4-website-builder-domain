<?php

declare(strict_types=1);

namespace Tests\Unit\Libraries\Hub;

use App\Libraries\Hub\HubClient;
use CodeIgniter\Cache\CacheInterface;
use CodeIgniter\HTTP\CURLRequest;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Test\CIUnitTestCase;
use dcardenasl\Ci4ApiCore\Http\Client\HubClientConfig;

/**
 * Covers the hardening of {@see HubClient::resolvePublicFileMeta()}: the Hub's
 * batch-meta endpoint caps a single request at 200 ids (see
 * `InternalFileMetaController::batchMeta`), so a miss set larger than that must
 * be split into chunks instead of silently truncating; and a transient Hub
 * failure must fall back to a longer-lived stale cache entry instead of
 * dropping the id entirely.
 *
 * @internal
 */
final class HubClientResolvePublicFileMetaTest extends CIUnitTestCase
{
    private function makeConfig(): HubClientConfig
    {
        return new HubClientConfig(
            url: 'http://hub.test',
            apiKey: 'test-key',
            introspectCacheTtl: 60,
            serviceTokenSafetyMargin: 30,
            httpTimeout: 5
        );
    }

    public function testChunksHubRequestsAtTheEndpointLimit(): void
    {
        $cache = $this->createMock(CacheInterface::class);
        $cache->method('get')->willReturn(null);
        $cache->expects($this->exactly(4))->method('save');

        $batchSizes = [];
        $http = $this->createMock(CURLRequest::class);
        $http->expects($this->exactly(2))
            ->method('request')
            ->willReturnCallback(function (string $method, string $url, array $options) use (&$batchSizes): ResponseInterface {
                $ids = $options['query']['ids'] ?? [];
                $batchSizes[] = count($ids);
                $id = (int) ($ids[0] ?? 0);

                return $this->jsonResponse(200, ['data' => [$id => ['id' => $id, 'url' => 'https://cdn.test/' . $id . '.jpg']]]);
            });

        $client = new HubClient($this->makeConfig(), $http, $cache);
        $result = $client->resolvePublicFileMeta(range(1, 201));

        $this->assertSame([200, 1], $batchSizes);
        $this->assertSame('https://cdn.test/1.jpg', $result[1]['url']);
        $this->assertSame('https://cdn.test/201.jpg', $result[201]['url']);
    }

    public function testReturnsStaleCacheWhenHubFails(): void
    {
        $cache = $this->createMock(CacheInterface::class);
        $cache->method('get')->willReturnCallback(static function (string $key): ?array {
            return str_contains($key, '_stale_') ? ['id' => 7, 'url' => 'https://cdn.test/stale.jpg'] : null;
        });
        $cache->expects($this->never())->method('save');

        // A persistent 5xx is retried once by the shared request() helper
        // (AbstractServiceClient's built-in retry-on-5xx behaviour) before
        // resolvePublicFileMeta's own catch block falls back to the stale
        // cache entry populated above.
        $http = $this->createMock(CURLRequest::class);
        $http->expects($this->exactly(2))->method('request')->willReturn($this->jsonResponse(503, []));

        $client = new HubClient($this->makeConfig(), $http, $cache);

        $this->assertSame(
            ['id' => 7, 'url' => 'https://cdn.test/stale.jpg'],
            $client->resolvePublicFileMeta([7])[7],
        );
    }

    public function testSanitizesAndDedupesIds(): void
    {
        $cache = $this->createMock(CacheInterface::class);
        $cache->method('get')->willReturn(null);

        $http = $this->createMock(CURLRequest::class);
        $http->expects($this->once())
            ->method('request')
            ->willReturnCallback(function (string $method, string $url, array $options): ResponseInterface {
                $this->assertSame([5], $options['query']['ids']);

                return $this->jsonResponse(200, ['data' => [5 => ['id' => 5, 'url' => 'https://cdn.test/5.jpg']]]);
            });

        $client = new HubClient($this->makeConfig(), $http, $cache);
        $result = $client->resolvePublicFileMeta([5, 5, 0, -1, 5]);

        $this->assertSame(['https://cdn.test/5.jpg'], array_column($result, 'url'));
    }

    private function jsonResponse(int $status, array $body): ResponseInterface
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn($status);
        $response->method('getBody')->willReturn(json_encode($body, JSON_THROW_ON_ERROR));

        return $response;
    }
}
