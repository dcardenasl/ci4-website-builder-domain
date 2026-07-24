<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Cms;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * HTTP smoke test for TranslationAuditController. The configured route group
 * wraps every endpoint in an auth filter — an unauthenticated request returns 401 — a sufficient signal that the route was registered and wired.
 *
 * @internal
 */
final class TranslationAuditControllerTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = true;
    protected $refresh     = true;
    protected $namespace   = 'App';

    public function testStatsSmoke(): void
    {
        $result = $this->get('/api/v1/cms/translations/audit/stats');
        $result->assertStatus(401);
    }

    public function testReportSmoke(): void
    {
        $result = $this->get('/api/v1/cms/translations/audit/report');
        $result->assertStatus(401);
    }

    public function testResourceSmoke(): void
    {
        $result = $this->get('/api/v1/cms/translations/audit/resource/page/1');
        $result->assertStatus(401);
    }

    public function testOwnerSmoke(): void
    {
        $result = $this->get('/api/v1/cms/translations/audit/owner/page/1');
        $result->assertStatus(401);
    }
}
