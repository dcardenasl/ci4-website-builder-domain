<?php

declare(strict_types=1);

namespace Tests\Unit\Libraries\Cms;

use App\Libraries\Cms\FileUrlResolver;
use App\Libraries\Hub\HubClient;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class FileUrlResolverTest extends CIUnitTestCase
{
    public function testOriginalContextPrefersStoredUrlOverVariants(): void
    {
        $hubClient = $this->createMock(HubClient::class);
        $hubClient->method('resolvePublicFileMeta')->willReturn([
            20 => [
                'url' => 'http://localhost:8180/uploads/2026/06/28/logo.gif',
                'variants' => [
                    'md' => ['url' => 'http://localhost:8180/uploads/2026/06/28/logo_md.gif'],
                    'sm' => ['url' => 'http://localhost:8180/uploads/2026/06/28/logo_sm.gif'],
                ],
            ],
        ]);

        $resolver = new FileUrlResolver($hubClient);

        $this->assertSame(
            'http://localhost:8180/uploads/2026/06/28/logo.gif',
            $resolver->resolve(20, 'original')
        );
        $this->assertSame(
            'http://localhost:8180/uploads/2026/06/28/logo_md.gif',
            $resolver->resolve(20, 'public')
        );
    }

    public function testOriginalContextFallsBackToStoredUrlWhenVariantsMissing(): void
    {
        $hubClient = $this->createMock(HubClient::class);
        $hubClient->method('resolvePublicFileMeta')->willReturn([
            21 => [
                'url' => 'http://localhost:8180/uploads/2026/06/28/brand.svg',
            ],
        ]);

        $resolver = new FileUrlResolver($hubClient);

        $this->assertSame(
            'http://localhost:8180/uploads/2026/06/28/brand.svg',
            $resolver->resolve(21, 'original')
        );
    }

    public function testNormalizeMediaReferencePreservesExternalUrl(): void
    {
        $hubClient = $this->createMock(HubClient::class);
        $resolver = new FileUrlResolver($hubClient);

        $reference = $resolver->normalizeMediaReference([
            'source_kind' => 'external_url',
            'url' => 'https://cdn.example.com/banner.jpg',
        ]);

        $this->assertSame('external_url', $reference['source_kind']);
        $this->assertNull($reference['file_id']);
        $this->assertSame('https://cdn.example.com/banner.jpg', $reference['url']);
    }

    public function testNormalizeMediaReferenceUsesExplicitHubFileId(): void
    {
        $hubClient = $this->createMock(HubClient::class);
        $hubClient->method('resolvePublicFileMeta')->willReturn([
            20 => [
                'url' => 'http://localhost:8180/uploads/2026/06/28/logo.gif',
            ],
        ]);

        $resolver = new FileUrlResolver($hubClient);

        $reference = $resolver->normalizeMediaReference([
            'source_kind' => 'hub_file',
            'file_id' => 20,
            'url' => null,
        ]);

        $this->assertSame('hub_file', $reference['source_kind']);
        $this->assertSame(20, $reference['file_id']);
        $this->assertSame('http://localhost:8180/uploads/2026/06/28/logo.gif', $reference['url']);
    }

    public function testNormalizeMediaReferenceIncludesVariants(): void
    {
        $hubClient = $this->createMock(HubClient::class);
        $hubClient->method('resolvePublicFileMeta')->willReturn([
            20 => [
                'url' => 'http://localhost:8180/uploads/2026/06/28/logo.gif',
                'variants' => [
                    'md' => ['url' => 'http://localhost:8180/uploads/2026/06/28/logo_md.gif'],
                ],
            ],
        ]);

        $resolver = new FileUrlResolver($hubClient);

        $reference = $resolver->normalizeMediaReference([
            'source_kind' => 'hub_file',
            'file_id' => 20,
            'url' => null,
        ]);

        $this->assertSame('hub_file', $reference['source_kind']);
        $this->assertSame(20, $reference['file_id']);
        $this->assertSame('http://localhost:8180/uploads/2026/06/28/logo_md.gif', $reference['url']);
        $this->assertSame(['md' => ['url' => 'http://localhost:8180/uploads/2026/06/28/logo_md.gif']], $reference['variants']);
    }

    public function testResolveManyMetaReturnsUrlsAndVariants(): void
    {
        $hubClient = $this->createMock(HubClient::class);
        $hubClient->method('resolvePublicFileMeta')->willReturn([
            20 => [
                'url' => 'http://localhost:8180/uploads/2026/06/28/logo.gif',
                'variants' => [
                    'md' => ['url' => 'http://localhost:8180/uploads/2026/06/28/logo_md.gif'],
                ],
            ],
        ]);

        $resolver = new FileUrlResolver($hubClient);
        $result = $resolver->resolveManyMeta([20]);

        $this->assertArrayHasKey(20, $result);
        $this->assertSame('http://localhost:8180/uploads/2026/06/28/logo_md.gif', $result[20]['url']);
        $this->assertSame(['md' => ['url' => 'http://localhost:8180/uploads/2026/06/28/logo_md.gif']], $result[20]['variants']);
    }
}
