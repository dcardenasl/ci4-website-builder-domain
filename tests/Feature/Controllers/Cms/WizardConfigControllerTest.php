<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Cms;

use App\Libraries\Hub\HubClient;
use Config\Services;
use dcardenasl\Ci4ApiCore\Http\Client\IntrospectResult;
use Tests\Support\ApiTestCase;
use Tests\Support\Fixtures\CmsFixtureFactory;

/**
 * Characterization coverage for WizardConfigController::config(), written
 * after extracting its logic into WizardConfigService (DOM-122) — locks in
 * the response shape (languages, collections, pages, menus, block_types,
 * field_primitives, block_capabilities, setup_state) so it stays correct
 * going forward, since no test existed for this endpoint before.
 *
 * @internal
 */
final class WizardConfigControllerTest extends ApiTestCase
{
    /** @var list<array{id:int,code:string,name:string,is_default:bool}> */
    private array $languages;

    private CmsFixtureFactory $fixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->authenticateRequest();

        $this->db->disableForeignKeyChecks();
        $this->db->query('DELETE FROM `cms_content_blocks`');
        $this->db->query('DELETE FROM `cms_menu_translations`');
        $this->db->query('DELETE FROM `cms_menus`');
        $this->db->query('DELETE FROM `cms_page_translations`');
        $this->db->query('DELETE FROM `cms_pages`');
        $this->db->query('DELETE FROM `cms_collection_translations`');
        $this->db->query('DELETE FROM `cms_collections`');
        $this->db->query('DELETE FROM `cms_languages`');
        $this->db->enableForeignKeyChecks();

        $this->fixtures = new CmsFixtureFactory($this->db, self::class);
        $this->languages = $this->fixtures->languages(1);
    }

    private function authenticateRequest(): void
    {
        $stub = new class (new IntrospectResult(
            valid: true,
            uid: 1,
            permissions: ['cms.entries.read'],
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

    public function testBuildsFullWizardConfigShape(): void
    {
        $defaultLanguageId = $this->languages[0]['id'];

        $collection = $this->fixtures->collection(
            [['language_id' => $defaultLanguageId, 'name' => 'Noticias', 'slug' => 'noticias']],
            ['wizard_config' => json_encode(['icon' => '📰', 'description' => 'News collection'])]
        );

        $page = $this->fixtures->page(
            [['language_id' => $defaultLanguageId, 'title' => 'Inicio', 'slug' => 'inicio']]
        );

        $menu = $this->fixtures->menu(
            [['language_id' => $defaultLanguageId, 'name' => 'Principal']]
        );

        $this->db->table('cms_content_blocks')->insert([
            'block_key'         => 'hero_banner',
            'name'              => 'Hero Banner',
            'description'       => 'A hero banner',
            'icon'              => 'image',
            'schema_definition' => json_encode([
                'fields'        => [
                    'heading' => ['type' => 'string', 'required' => true],
                ],
                'config_fields' => [
                    'style' => ['type' => 'string'],
                ],
            ]),
            'supports_pages'    => 1,
            'supports_entries'  => 0,
            'is_container'      => 0,
            'is_active'         => 1,
            'sort_order'        => 0,
        ]);

        $result = $this->get('api/v1/cms/wizard/config');

        $result->assertStatus(200);
        $body = json_decode((string) $result->response()->getBody(), true);

        $this->assertSame('success', $body['status']);
        $data = $body['data'];

        $this->assertSame($defaultLanguageId, $data['default_language_id']);
        $this->assertCount(1, $data['languages']);
        $this->assertSame($this->languages[0]['code'], $data['languages'][0]['code']);

        $this->assertCount(1, $data['collections']);
        $this->assertSame($collection['id'], $data['collections'][0]['id']);
        $this->assertSame('Noticias', $data['collections'][0]['name']);
        $this->assertSame('📰', $data['collections'][0]['icon']);
        $this->assertSame('News collection', $data['collections'][0]['description']);

        $this->assertCount(1, $data['pages']);
        $this->assertSame($page['id'], $data['pages'][0]['id']);
        $this->assertSame('Inicio', $data['pages'][0]['title']);
        $this->assertSame('inicio', $data['pages'][0]['slug']);

        $this->assertCount(1, $data['menus']);
        $this->assertSame($menu['id'], $data['menus'][0]['id']);
        $this->assertSame('Principal', $data['menus'][0]['name']);

        $this->assertArrayHasKey('hero_banner', $data['block_types']);
        $this->assertSame('Hero Banner', $data['block_types']['hero_banner']['name']);
        $this->assertArrayHasKey('heading', $data['block_types']['hero_banner']['fields']);
        $this->assertSame(['style' => ['type' => 'string']], $data['block_types']['hero_banner']['config_fields']);
        $this->assertSame(['heading'], $data['block_types']['hero_banner']['capabilities']['required_fields']);

        $this->assertArrayHasKey('hero_banner', $data['block_capabilities']);
        $this->assertSame($data['block_types']['hero_banner']['capabilities'], $data['block_capabilities']['hero_banner']);

        $this->assertIsArray($data['field_primitives']);
        $this->assertIsArray($data['non_translatable_types']);

        $this->assertTrue($data['setup_state']['has_languages']);
        $this->assertTrue($data['setup_state']['has_collections']);
        $this->assertTrue($data['setup_state']['has_active_block_types']);
    }

    public function testForbidsRequestsWithoutEntriesReadPermission(): void
    {
        $stub = new class (new IntrospectResult(
            valid: true,
            uid: 1,
            permissions: [],
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
        $this->resetRequest();

        $result = $this->get('api/v1/cms/wizard/config');

        $result->assertStatus(403);
    }
}
