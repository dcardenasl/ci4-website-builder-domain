<?php

declare(strict_types=1);

namespace Tests\Unit\Filters;

use App\Filters\ThrottleFilter;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\URI;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class ThrottleFilterTest extends CIUnitTestCase
{
    public function testPublicReadsUseAnAppKeyBucket(): void
    {
        $request = $this->createMock(RequestInterface::class);
        $request->method('getMethod')->willReturn('GET');
        $request->method('getHeaderLine')->with('X-App-Key')->willReturn('web-app-secret');
        $request->method('getUri')->willReturn(new URI('http://localhost/api/v1/public/es/collections'));

        $buckets = $this->exposedFilter()->buckets($request);

        $this->assertCount(1, $buckets);
        $this->assertSame(
            'rate_limit_public_read_app_' . hash('sha256', 'web-app-secret'),
            $buckets[0]['key']
        );
        $this->assertSame(600, $buckets[0]['limit']);
        $this->assertSame(60, $buckets[0]['window']);
    }

    public function testNonPublicRequestsRetainCoreBuckets(): void
    {
        $request = $this->createMock(RequestInterface::class);
        $request->method('getMethod')->willReturn('GET');
        $request->method('getHeaderLine')->with('X-App-Key')->willReturn('web-app-secret');
        $request->method('getUri')->willReturn(new URI('http://localhost/api/v1/cms/pages'));
        $request->method('getIPAddress')->willReturn('192.0.2.10');

        $buckets = $this->exposedFilter()->buckets($request);

        $this->assertSame('rate_limit_ip_' . md5('192.0.2.10'), $buckets[0]['key']);
    }

    private function exposedFilter(): object
    {
        return new class () extends ThrottleFilter {
            /**
             * @return list<array{key: string, limit: int, window: int}>
             */
            public function buckets(RequestInterface $request): array
            {
                return $this->resolveBuckets($request);
            }
        };
    }
}
