<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\Support\ApiTestCase;

/**
 * Translation audit flow tests.
 *
 * Smoke tests to verify translation audit endpoints respond correctly.
 *
 * @internal
 */
final class TranslationAuditFlowTest extends ApiTestCase
{
    /**
     * Test 1: Audit report endpoint is accessible.
     */
    public function testAuditReportEndpointSmoke(): void
    {
        $result = $this->get('/api/v1/cms/translations/audit/report');
        // Endpoint exists (not 404)
        $this->assertNotEquals(404, $result->response()->getStatusCode());
    }

    /**
     * Test 2: Audit stats endpoint is accessible.
     */
    public function testAuditStatsEndpointSmoke(): void
    {
        $result = $this->get('/api/v1/cms/translations/audit/stats');
        // Endpoint exists (not 404)
        $this->assertNotEquals(404, $result->response()->getStatusCode());
    }

    /**
     * Test 3: Resource audit endpoint is accessible for pages.
     */
    public function testAuditResourcePageEndpointSmoke(): void
    {
        $result = $this->get('/api/v1/cms/translations/audit/resource/page/1');
        // Endpoint exists (not 404)
        $this->assertNotEquals(404, $result->response()->getStatusCode());
    }

    /**
     * Test 4: Resource audit endpoint is accessible for menus.
     */
    public function testAuditResourceMenuEndpointSmoke(): void
    {
        $result = $this->get('/api/v1/cms/translations/audit/resource/menu/1');
        // Endpoint exists (not 404)
        $this->assertNotEquals(404, $result->response()->getStatusCode());
    }

    /**
     * Test 5: Report endpoint accepts language_id parameter.
     */
    public function testAuditReportWithLanguageFilterSmoke(): void
    {
        $result = $this->get('/api/v1/cms/translations/audit/report?language_id=1');
        // Endpoint exists (not 404)
        $this->assertNotEquals(404, $result->response()->getStatusCode());
    }
}
