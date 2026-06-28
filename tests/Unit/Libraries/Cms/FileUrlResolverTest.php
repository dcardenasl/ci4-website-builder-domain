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
}
