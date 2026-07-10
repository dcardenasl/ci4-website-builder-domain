<?php

declare(strict_types=1);

namespace Tests\Unit\Libraries\Cms;

use App\Libraries\Cms\PreviewToken;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class PreviewTokenTest extends CIUnitTestCase
{
    private const SECRET = 'testing-preview-secret-at-least-32-characters';

    private function sign(string $type, string $identifier, int $expires): string
    {
        return hash_hmac('sha256', $type . ':' . $identifier . ':' . $expires, self::SECRET);
    }

    public function testValidSignatureVerifiesForAnEntryId(): void
    {
        $expires = (string) (time() + 3600);
        $sig = $this->sign('entry', '42', (int) $expires);

        $this->assertTrue(PreviewToken::verify('entry', '42', $expires, $sig));
    }

    public function testValidSignatureVerifiesForALangSlugPage(): void
    {
        $expires = (string) (time() + 3600);
        $sig = $this->sign('page', 'es:nosotros', (int) $expires);

        $this->assertTrue(PreviewToken::verify('page', 'es:nosotros', $expires, $sig));
    }

    public function testWrongIdentifierFailsVerification(): void
    {
        $expires = (string) (time() + 3600);
        $sig = $this->sign('entry', '42', (int) $expires);

        $this->assertFalse(PreviewToken::verify('entry', '43', $expires, $sig));
    }

    public function testWrongResourceTypeFailsVerification(): void
    {
        $expires = (string) (time() + 3600);
        $sig = $this->sign('page', 'es:nosotros', (int) $expires);

        $this->assertFalse(PreviewToken::verify('entry', 'es:nosotros', $expires, $sig));
    }

    public function testExpiredLinkFailsVerification(): void
    {
        $expires = (string) (time() - 60);
        $sig = $this->sign('entry', '42', (int) $expires);

        $this->assertFalse(PreviewToken::verify('entry', '42', $expires, $sig));
    }

    public function testTamperedSignatureFailsVerification(): void
    {
        $expires = (string) (time() + 3600);

        $this->assertFalse(PreviewToken::verify('entry', '42', $expires, 'not-a-real-signature'));
    }

    public function testMissingParamsFailVerification(): void
    {
        $this->assertFalse(PreviewToken::verify('entry', '42', null, null));
        $this->assertFalse(PreviewToken::verify('entry', '42', '', ''));
    }

    public function testNonNumericExpiresFailsVerification(): void
    {
        $this->assertFalse(PreviewToken::verify('entry', '42', 'not-a-number', 'whatever'));
    }

    public function testMissingSecretFailsClosedEvenWithACorrectlyComputedSignature(): void
    {
        putenv('CMS_PREVIEW_SECRET');
        unset($_ENV['CMS_PREVIEW_SECRET'], $_SERVER['CMS_PREVIEW_SECRET']);

        try {
            $expires = (string) (time() + 3600);
            // Signed with an empty secret — mirrors what an attacker gets if the
            // app is misconfigured and they guess the (now trivial) HMAC.
            $sig = hash_hmac('sha256', 'entry:42:' . $expires, '');

            $this->assertFalse(PreviewToken::verify('entry', '42', $expires, $sig));
        } finally {
            putenv('CMS_PREVIEW_SECRET=' . self::SECRET);
            $_ENV['CMS_PREVIEW_SECRET'] = self::SECRET;
            $_SERVER['CMS_PREVIEW_SECRET'] = self::SECRET;
        }
    }
}
