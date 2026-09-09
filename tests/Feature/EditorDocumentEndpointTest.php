<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Libraries\Hub\HubClient;
use Config\Database;
use Config\Services;
use dcardenasl\Ci4ApiCore\Http\Client\IntrospectResult;
use Tests\Support\ApiTestCase;
use Tests\Support\Traits\WithWebAppKeyTrait;

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
    use WithWebAppKeyTrait;

    private int $langEsId = 0;
    private int $langEnId = 0;
    private int $blockTypeId = 0;
    private int $pageId = 0;
    private int $instanceId = 0;
    private string $blockKey = '';

    protected function setUp(): void
    {
        parent::setUp();
        $this->configureWebAppKey();
        $this->seedLanguages();
        $this->seedDocument();
    }

    protected function tearDown(): void
    {
        $this->restoreWebAppKey();
        parent::tearDown();
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

    public function testAnEditorSavesABlockInOneTransaction(): void
    {
        $this->authenticateWith(['cms.pages.write']);
        $version = $this->documentVersion();

        $result = $this->postJson('/api/v1/cms/editor/pages/' . $this->pageId . '/document', [
            'base_version' => $version,
            'ops' => [[
                'op' => 'upsert',
                'instance_id' => $this->instanceId,
                'i18n' => ['es' => ['title' => 'Editado desde el canvas']],
            ]],
        ]);

        $result->assertStatus(200);
        $payload = json_decode((string) $result->getJSON(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('saved', $payload['data']['code']);
        self::assertNotSame($version, $payload['data']['document']['version']);

        $stored = Database::connect()->table('cms_block_instance_translations')
            ->where('instance_id', $this->instanceId)->where('language_id', $this->langEsId)->get()->getRowArray();
        self::assertStringContainsString('Editado desde el canvas', (string) ($stored['block_data'] ?? ''));
    }

    public function testAStaleRevisionIsRejectedWithTheCurrentDocument(): void
    {
        $this->authenticateWith(['cms.pages.write']);
        $stale = $this->documentVersion();

        Database::connect()->table('cms_block_instance_translations')
            ->where('instance_id', $this->instanceId)->where('language_id', $this->langEsId)
            ->update(['block_data' => json_encode(['title' => 'Desde el CRUD'], JSON_THROW_ON_ERROR)]);

        $result = $this->postJson('/api/v1/cms/editor/pages/' . $this->pageId . '/document', [
            'base_version' => $stale,
            'ops' => [['op' => 'upsert', 'instance_id' => $this->instanceId, 'i18n' => ['es' => ['title' => 'Desde el canvas']]]],
        ]);

        $result->assertStatus(409);
        $payload = json_decode((string) $result->getJSON(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('version_conflict', $payload['data']['code']);
        self::assertSame('Desde el CRUD', $payload['data']['document']['blocks'][0]['i18n']['es']['title']);
    }

    public function testAFieldOutsideTheBlockSchemaIsRejected(): void
    {
        $this->authenticateWith(['cms.pages.write']);

        $result = $this->postJson('/api/v1/cms/editor/pages/' . $this->pageId . '/document', [
            'base_version' => $this->documentVersion(),
            'ops' => [['op' => 'upsert', 'instance_id' => $this->instanceId, 'i18n' => ['es' => ['ghost' => 'x']]]],
        ]);

        $result->assertStatus(422);
    }

    public function testAnEntryPermissionCannotSaveAPage(): void
    {
        $this->authenticateWith(['cms.entries.write']);

        $this->postJson('/api/v1/cms/editor/pages/' . $this->pageId . '/document', [
            'base_version' => str_repeat('a', 64),
            'ops' => [['op' => 'delete', 'instance_id' => $this->instanceId]],
        ])->assertStatus(403);
    }

    public function testTheProjectionResolvesADraftForThePublicRenderer(): void
    {
        $this->authenticateWith(['cms.pages.write']);

        $result = $this->postProjection($this->pageId, [
            'lang' => 'es',
            'scope' => ['type' => 'document'],
            'blocks' => [[
                'instance_id' => $this->instanceId,
                'block_key' => $this->blockKey,
                'sort_order' => 1,
                'config' => [],
                'i18n' => ['es' => ['title' => 'Titular del borrador'], 'en' => []],
            ]],
        ]);

        $result->assertStatus(200);
        $document = json_decode((string) $result->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data'];

        self::assertSame('es', $document['lang']);
        self::assertCount(1, $document['blocks']);
        self::assertSame('Titular del borrador', $document['blocks'][0]['block_data']['title']);
        self::assertSame($this->blockKey, $document['blocks'][0]['block_key']);
    }

    /**
     * A preview exists to show what saving would produce, so it must clean the
     * draft exactly as the writer does — this app purifies any string that looks
     * like markup, wherever it sits.
     */
    public function testTheProjectionSanitizesTheDraftLikeSavingWould(): void
    {
        $this->authenticateWith(['cms.pages.write']);
        $dirty = '<p>ok</p><script>alert(1)</script>';

        $result = $this->postProjection($this->pageId, [
            'lang' => 'es',
            'scope' => ['type' => 'document'],
            'blocks' => [[
                'instance_id' => $this->instanceId,
                'block_key' => $this->blockKey,
                'sort_order' => 1,
                'config' => [],
                'i18n' => ['es' => ['title' => $dirty], 'en' => []],
            ]],
        ]);

        $result->assertStatus(200);
        $rendered = json_decode((string) $result->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['blocks'][0]['block_data']['title'];
        self::assertStringNotContainsString('<script>', $rendered);

        // The same value through the writer, for comparison.
        $this->postJson('/api/v1/cms/editor/pages/' . $this->pageId . '/document', [
            'base_version' => $this->documentVersion(),
            'ops' => [['op' => 'upsert', 'instance_id' => $this->instanceId, 'i18n' => ['es' => ['title' => $dirty]]]],
        ])->assertStatus(200);
        $stored = Database::connect()->table('cms_block_instance_translations')
            ->where('instance_id', $this->instanceId)->where('language_id', $this->langEsId)->get()->getRowArray();
        $saved = json_decode((string) ($stored['block_data'] ?? '{}'), true, 512, JSON_THROW_ON_ERROR)['title'];

        self::assertSame($saved, $rendered, 'Preview and save must clean identically.');
    }

    /**
     * The canvas exists to show what a visitor gets. Preview and the public
     * reader therefore have to resolve a partial translation the same way — a
     * translated title kept, only the missing body borrowed.
     */
    public function testPreviewAndThePublicReaderResolveAPartialTranslationAlike(): void
    {
        $this->authenticateWith(['cms.pages.write']);
        Database::connect()->table('cms_block_instance_translations')->insert([
            'instance_id' => $this->instanceId,
            'language_id' => $this->langEnId,
            'block_data' => json_encode(['title' => ''], JSON_THROW_ON_ERROR),
        ]);

        $projected = json_decode((string) $this->postProjection($this->pageId, [
            'lang' => 'en',
            'scope' => ['type' => 'document'],
            'blocks' => [[
                'instance_id' => $this->instanceId,
                'block_key' => $this->blockKey,
                'sort_order' => 1,
                'config' => [],
                'i18n' => ['es' => ['title' => 'Titular'], 'en' => ['title' => '']],
            ]],
        ])->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['blocks'][0];

        $published = (new \App\Libraries\Cms\BlockInstanceSerializer(\Config\Services::fileUrlResolver()))
            ->forContent('page', $this->pageId, 'en')[0];

        self::assertSame('Titular', $projected['block_data']['title'], 'An empty translation borrows the default copy.');
        self::assertSame($published['block_data']['title'], $projected['block_data']['title'], 'Preview and the live site must agree.');
        self::assertSame($published['fallback_fields'], $projected['fallback_fields']);
    }

    public function testTheProjectionRefusesABlockOfAnotherDocument(): void
    {
        $this->authenticateWith(['cms.pages.write']);

        $this->postProjection($this->pageId, [
            'lang' => 'es',
            'scope' => ['type' => 'document'],
            'blocks' => [[
                'instance_id' => 987654,
                'block_key' => $this->blockKey,
                'sort_order' => 1,
                'config' => [],
                'i18n' => ['es' => []],
            ]],
        ])->assertStatus(422);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function postJson(string $uri, array $payload): \CodeIgniter\Test\TestResponse
    {
        // withHeaders() replaces the set rather than merging, so each helper
        // states everything its own call needs.
        return $this->withHeaders(['Authorization' => 'Bearer fake-test-token', 'Content-Type' => 'application/json'])
            ->withBody(json_encode($payload, JSON_THROW_ON_ERROR))
            ->call('post', $uri);
    }

    /** The projection is an app-key call: the public site asks on the visitor's behalf. */
    private function postProjection(int $ownerId, array $payload): \CodeIgniter\Test\TestResponse
    {
        return $this->withHeaders($this->webAppKeyHeader() + ['Content-Type' => 'application/json'])
            ->withBody(json_encode($payload, JSON_THROW_ON_ERROR))
            ->call('post', '/api/v1/public/editor-projection/pages/' . $ownerId);
    }

    private function documentVersion(): string
    {
        $result = $this->withHeaders(['Authorization' => 'Bearer fake-test-token'])
            ->call('get', '/api/v1/cms/editor/pages/' . $this->pageId . '/document');
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
        // Content-Type travels with the token: withHeaders() replaces the set
        // rather than adding to it, and a patch without it loses its auth.
        $this->setTestRequestHeaders(['Authorization' => 'Bearer fake-test-token', 'Content-Type' => 'application/json']);
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
        $this->blockKey = $unique;

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
