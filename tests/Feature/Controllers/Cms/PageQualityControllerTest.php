<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Cms;

use App\Libraries\Hub\HubClient;
use Config\Services;
use dcardenasl\Ci4ApiCore\Http\Client\IntrospectResult;
use Tests\Support\ApiTestCase;
use Tests\Support\Fixtures\CmsFixtureFactory;

final class PageQualityControllerTest extends ApiTestCase
{
    public function testReturnsCmsOwnedQualityReport(): void
    {
        $this->authenticateRequest();
        $this->db->disableForeignKeyChecks();
        $this->db->query('DELETE FROM `cms_block_instances`');
        $this->db->query('DELETE FROM `cms_content_blocks`');
        $this->db->query('DELETE FROM `cms_page_translations`');
        $this->db->query('DELETE FROM `cms_pages`');
        $this->db->query('DELETE FROM `cms_languages`');
        $this->db->enableForeignKeyChecks();

        $fixtures = new CmsFixtureFactory($this->db, self::class);
        $languages = $fixtures->languages(1);
        $page = $fixtures->page([
            [
                'language_id' => $languages[0]['id'],
                'title' => 'Página de prueba',
                'slug' => 'pagina-de-prueba',
            ],
        ]);

        $this->db->table('cms_content_blocks')->insert([
            'block_key' => 'page_header',
            'name' => 'Page header',
            'description' => 'Heading owner',
            'category' => 'content',
            'schema_definition' => json_encode([
                'presentation' => ['owns_page_heading' => true],
                'fields' => [],
            ], JSON_THROW_ON_ERROR),
            'supports_pages' => 1,
            'supports_entries' => 0,
            'is_container' => 0,
            'is_active' => 1,
            'sort_order' => 0,
        ]);
        $blockTypeId = (int) $this->db->insertID();
        $fixtures->block($blockTypeId, 'page', $page['id']);

        $result = $this->get('/api/v1/cms/pages/' . $page['id'] . '/quality');

        $result->assertStatus(200);
        $body = json_decode((string) $result->response()->getBody(), true);
        $this->assertSame('page-quality.v2', $body['data']['version'] ?? null);

        $headingCheck = array_values(array_filter(
            $body['data']['checks'] ?? [],
            static fn (array $check): bool => ($check['key'] ?? '') === 'page_heading_owner'
        ));
        $this->assertSame('pass', $headingCheck[0]['status'] ?? null);

        foreach ($body['data']['checks'] ?? [] as $check) {
            $this->assertArrayHasKey('message_key', $check);
            $this->assertArrayNotHasKey('message', $check);
        }
    }

    public function testQualityRequiresAuthentication(): void
    {
        $result = $this->get('/api/v1/cms/pages/99999/quality');

        $result->assertStatus(401);
    }

    private function authenticateRequest(): void
    {
        $stub = new class (new IntrospectResult(
            valid: true,
            uid: 1,
            permissions: ['cms.pages.read', 'iam.superadmin-access'],
            exp: time() + 3600,
            error: null
        )) extends HubClient {
            public function __construct(private readonly IntrospectResult $result)
            {
            }

            public function introspect(string $token): IntrospectResult
            {
                return $this->result;
            }
        };

        Services::injectMock('hubClient', $stub);
        $this->setTestRequestHeaders(['Authorization' => 'Bearer fake-test-token']);
    }
}
