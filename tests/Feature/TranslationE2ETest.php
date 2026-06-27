<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\Support\ApiTestCase;

/**
 * End-to-end translation tests for multilingual content flow.
 *
 * Smoke tests to verify translation audit endpoints are wired and responding.
 *
 * @internal
 */
final class TranslationE2ETest extends ApiTestCase
{
    /**
     * Test 1: Page creation endpoint exists and returns 401 without auth.
     */
    public function testCreatePageEndpointRequiresAuth(): void
    {
        $result = $this->post('/api/v1/cms/pages', ['status' => 'published']);
        $result->assertStatus(401);
    }

    /**
     * Test 2: Page show endpoint exists and returns 401 without auth.
     */
    public function testPageShowEndpointRequiresAuth(): void
    {
        $result = $this->get('/api/v1/cms/pages/1');
        $result->assertStatus(401);
    }

    /**
     * Test 3: Translation audit report endpoint is accessible.
     */
    public function testTranslationAuditReportEndpointSmoke(): void
    {
        $result = $this->get('/api/v1/cms/translations/audit/report');
        // Endpoint exists (not 404)
        $this->assertNotEquals(404, $result->response()->getStatusCode());
    }

    /**
     * Test 4: Translation audit stats endpoint is accessible.
     */
    public function testTranslationAuditStatsEndpointSmoke(): void
    {
        $result = $this->get('/api/v1/cms/translations/audit/stats');
        // Endpoint exists (not 404)
        $this->assertNotEquals(404, $result->response()->getStatusCode());
    }

    /**
     * Test 5: Resource-level audit endpoint is accessible for pages.
     */
    public function testTranslationAuditResourcePageEndpointSmoke(): void
    {
        $result = $this->get('/api/v1/cms/translations/audit/resource/page/1');
        // Endpoint exists (not 404)
        $this->assertNotEquals(404, $result->response()->getStatusCode());
    }
}
