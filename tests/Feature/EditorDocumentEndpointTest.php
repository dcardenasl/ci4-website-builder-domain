<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Libraries\Hub\HubClient;
use Config\Database;
use Config\Services;
use dcardenasl\Ci4ApiCore\Http\Client\IntrospectResult;
use Tests\Support\ApiTestCase;

/**
 * The document contract the visual editor loads.
 *
 * Unlike the public serializer, this one hands back the raw value of every
 * active language: the canvas has to show an empty field where a translation is
 * missing, never the fallback dressed up as one.
 *
 * @internal
 */
final class EditorDocumentEndpointTest extends ApiTestCase
{
    private int $langEsId = 0;
    private int $langEnId = 0;
    private int $blockTypeId = 0;
    private int $pageId = 0;
    private int $instanceId = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedLanguages();
        $this->seedDocument();
    }

    public function testAnEditorLoadsEveryLanguageRawForItsPage(): void
    {
        $this->authenticateWith(['cms.pages.write']);

        $result = $this->call('get', '/api/v1/cms/editor/pages/' . $this->pageId . '/document');

        $result->assertStatus(200);
        $payload = json_decode((string) $result->getJSON(), true, 512, JSON_THROW_ON_ERROR);
        $document = $payload['data'];

        self::assertSame($this->pageId, $document['owner']['id']);
        self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/', (string) $document['version']);
        self::assertSame(['es', 'en'], array_column($document['locales'], 'code'));

        $block = $document['blocks'][0];
        self::assertSame($this->instanceId, $block['instance_id']);
        self::assertSame('Titular', $block['i18n']['es']['title']);
        self::assertSame([], $block['i18n']['en'], 'An untranslated language stays empty, never filled with the fallback.');
        self::assertNotEmpty($document['catalog']);
    }

    public function testTheRevisionChangesWhenAClassicWriteTouchesTheDocument(): void
    {
        $this->authenticateWith(['cms.pages.write']);
        $first = $this->documentVersion();

        Database::connect()->table('cms_block_instance_translations')
            ->where('instance_id', $this->instanceId)
            ->where('language_id', $this->langEsId)
            ->update(['block_data' => json_encode(['title' => 'Editado fuera del canvas'], JSON_THROW_ON_ERROR)]);

        self::assertNotSame($first, $this->documentVersion());
    }

    public function testAnEntryPermissionDoesNotOpenAPage(): void
    {
        $this->authenticateWith(['cms.entries.write']);

        $this->call('get', '/api/v1/cms/editor/pages/' . $this->pageId . '/document')->assertStatus(403);
    }

    public function testAMissingDocumentIsNotFound(): void
    {
        $this->authenticateWith(['cms.pages.write']);

        $this->call('get', '/api/v1/cms/editor/pages/99999999/document')->assertStatus(404);
    }

    private function documentVersion(): string
    {
        $result = $this->call('get', '/api/v1/cms/editor/pages/' . $this->pageId . '/document');
        $result->assertStatus(200);
        $payload = json_decode((string) $result->getJSON(), true, 512, JSON_THROW_ON_ERROR);

        return (string) $payload['data']['version'];
    }

    /** @param list<string> $permissions */
    private function authenticateWith(array $permissions): void
    {
        $stub = new class (new IntrospectResult(valid: true, uid: 1, permissions: $permissions, exp: time() + 3600, error: null)) extends HubClient {
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

    private function seedLanguages(): void
    {
        $db = Database::connect();
        $db->disableForeignKeyChecks();
        $db->table('cms_languages')->truncate();
        $db->enableForeignKeyChecks();

        $db->table('cms_languages')->insert(['code' => 'es', 'name' => 'Spanish', 'is_default' => 1, 'is_active' => 1, 'sort_order' => 0]);
        $this->langEsId = (int) $db->insertID();
        $db->table('cms_languages')->insert(['code' => 'en', 'name' => 'English', 'is_default' => 0, 'is_active' => 1, 'sort_order' => 1]);
        $this->langEnId = (int) $db->insertID();
    }

    /** This case does not refresh the schema between tests, so every row it
     *  creates carries a key of its own. */
    private function seedDocument(): void
    {
        $db = Database::connect();
        $unique = 'editor_copy_' . bin2hex(random_bytes(4));

        $db->table('cms_content_blocks')->insert([
            'block_key' => $unique,
            'name' => 'Copy',
            'schema_definition' => '{"fields":{"title":{"type":"string"}},"config_fields":{}}',
            'is_active' => 1,
            'supports_pages' => 1,
        ]);
        $this->blockTypeId = (int) $db->insertID();

        $db->table('cms_pages')->insert(['page_type' => 'generic', 'status' => 'draft']);
        $this->pageId = (int) $db->insertID();
        $db->table('cms_page_translations')->insert([
            'page_id' => $this->pageId, 'language_id' => $this->langEsId, 'slug' => 'editor-doc-' . $this->pageId, 'title' => 'Documento',
        ]);

        $db->table('cms_block_instances')->insert([
            'block_id' => $this->blockTypeId, 'owner_type' => 'page', 'owner_id' => $this->pageId, 'sort_order' => 1,
        ]);
        $this->instanceId = (int) $db->insertID();
        $db->table('cms_block_instance_translations')->insert([
            'instance_id' => $this->instanceId,
            'language_id' => $this->langEsId,
            'block_data' => json_encode(['title' => 'Titular'], JSON_THROW_ON_ERROR),
        ]);
    }
}
