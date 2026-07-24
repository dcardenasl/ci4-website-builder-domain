<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Libraries\Hub\HubClient;
use Config\Database;
use Config\Services;
use dcardenasl\Ci4ApiCore\Http\Client\IntrospectResult;
use Tests\Support\ApiTestCase;

/**
 * @internal
 */
final class CollectionBlockTemplateTest extends ApiTestCase
{
    private int $langEsId = 0;
    private int $langEnId = 0;
    private int $activeBlockId = 0;
    private int $secondActiveBlockId = 0;
    private int $inactiveBlockId = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedLanguages();
        $this->seedBlockTypes();
        $this->authenticateRequest();
    }

    private function authenticateRequest(): void
    {
        $stub = new class (new IntrospectResult(
            valid: true,
            uid: 1,
            permissions: [
                'cms.collections.write',
                'cms.collections.admin',
                'cms.collections.read',
                'cms.entries.write',
                'cms.entries.admin',
                'cms.entries.read',
                'cms.pages.write',
            ],
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

    private function seedLanguages(): void
    {
        $db = Database::connect();
        $db->disableForeignKeyChecks();
        $db->table('cms_languages')->truncate();
        $db->enableForeignKeyChecks();

        $db->table('cms_languages')->insert([
            'code'       => 'es',
            'name'       => 'Spanish',
            'is_default' => 1,
            'is_active'  => 1,
        ]);
        $this->langEsId = (int) $db->insertID();

        $db->table('cms_languages')->insert([
            'code'       => 'en',
            'name'       => 'English',
            'is_default' => 0,
            'is_active'  => 1,
        ]);
        $this->langEnId = (int) $db->insertID();
    }

    private function seedBlockTypes(): void
    {
        $db = Database::connect();
        $db->disableForeignKeyChecks();
        $db->table('cms_content_blocks')->truncate();
        $db->enableForeignKeyChecks();

        $db->table('cms_content_blocks')->insert([
            'block_key' => 'rich_text',
            'name' => 'Rich Text',
            'description' => null,
            'category' => 'content',
            'icon' => 'align-left',
            'schema_definition' => json_encode([
                'fields' => [
                    'body' => ['type' => 'string']
                ]
            ]),
            'supports_pages' => 1,
            'supports_entries' => 1,
            'is_container' => 0,
            'is_active' => 1,
            'sort_order' => 1,
        ]);
        $this->activeBlockId = (int) $db->insertID();

        $db->table('cms_content_blocks')->insert([
            'block_key' => 'image',
            'name' => 'Image',
            'description' => null,
            'category' => 'media',
            'icon' => 'image',
            'schema_definition' => json_encode([
                'fields' => [
                    'alt' => ['type' => 'string']
                ]
            ]),
            'supports_pages' => 1,
            'supports_entries' => 1,
            'is_container' => 0,
            'is_active' => 1,
            'sort_order' => 2,
        ]);
        $this->secondActiveBlockId = (int) $db->insertID();

        $db->table('cms_content_blocks')->insert([
            'block_key' => 'inactive_block',
            'name' => 'Inactive',
            'description' => null,
            'category' => 'content',
            'icon' => 'ban',
            'schema_definition' => '{}',
            'supports_pages' => 1,
            'supports_entries' => 1,
            'is_container' => 0,
            'is_active' => 0,
            'sort_order' => 2,
        ]);
        $this->inactiveBlockId = (int) $db->insertID();
    }

    public function testCreateCollectionWithTemplateSuccess(): void
    {
        $payload = [
            'collection_type' => 'post',
            'collection_key' => 'festivals',
            'is_active' => 'true',
            'requires_approval' => 'false',
            'enables_categories' => 'false',
            'enables_tags' => 'false',
            'sort_order' => '1',
            'block_template' => [
                'version' => '1.0',
                'blocks' => [
                    [
                        'block_key' => 'rich_text',
                        'sort_order' => '1',
                        'required' => 'true',
                        'locked' => 'true',
                        'block_config_defaults' => ['theme' => 'light'],
                    ],
                ],
            ],
            'translations' => [
                [
                    'language_id' => (string) $this->langEsId,
                    'slug' => 'festivales',
                    'name' => 'Festivales',
                ],
            ],
        ];

        $result = $this->post('/api/v1/cms/collections', $payload);
        $result->assertStatus(200);

        $body = $this->getResponseJson($result);
        $this->assertSame('success', $body['status']);
        $this->assertNotNull($body['data']['block_template']);

        $dbTemplate = Database::connect()
            ->table('cms_collections')
            ->where('collection_key', 'festivals')
            ->get()
            ->getRowArray()['block_template'];

        $this->assertNotEmpty($dbTemplate);
        $decoded = json_decode($dbTemplate, true);
        $this->assertSame('1.0', $decoded['version']);
        $this->assertSame('rich_text', $decoded['blocks'][0]['block_key']);
        $this->assertTrue($decoded['blocks'][0]['locked']);
    }

    public function testCreateCollectionWithoutTemplateSuccess(): void
    {
        $payload = [
            'collection_type' => 'post',
            'collection_key' => 'news',
            'is_active' => 'true',
            'requires_approval' => 'false',
            'enables_categories' => 'false',
            'enables_tags' => 'false',
            'sort_order' => '2',
            'translations' => [
                [
                    'language_id' => (string) $this->langEsId,
                    'slug' => 'noticias',
                    'name' => 'Noticias',
                ],
            ],
        ];

        $result = $this->post('/api/v1/cms/collections', $payload);
        $result->assertStatus(200);

        $body = $this->getResponseJson($result);
        $this->assertSame('success', $body['status']);
        $this->assertNull($body['data']['block_template']);

        $dbTemplate = Database::connect()
            ->table('cms_collections')
            ->where('collection_key', 'news')
            ->get()
            ->getRowArray()['block_template'];

        $this->assertNull($dbTemplate);
    }

    public function testAutoInitializeBlocksOnEntryCreation(): void
    {
        $db = Database::connect();
        // Insert collection directly to bypass routing
        $template = [
            'version' => '1.0',
            'blocks' => [
                [
                    'block_key' => 'rich_text',
                    'sort_order' => 1,
                    'required' => true,
                    'locked' => true,
                    'block_config_defaults' => ['theme' => 'light'],
                ],
            ],
        ];

        $db->table('cms_collections')->insert([
            'collection_type' => 'post',
            'collection_key' => 'events',
            'is_active' => 1,
            'requires_approval' => 0,
            'enables_categories' => 0,
            'enables_tags' => 0,
            'sort_order' => 3,
            'block_template' => json_encode($template),
        ]);
        $collectionId = (int) $db->insertID();

        $payload = [
            'collection_id' => (string) $collectionId,
            'workflow_status' => 'published',
            'is_featured' => 'false',
            'translations' => [
                [
                    'language_id' => (string) $this->langEsId,
                    'slug' => 'concert',
                    'title' => 'Concert',
                ],
            ],
        ];

        $result = $this->post('/api/v1/cms/entries', $payload);
        $result->assertStatus(200);

        $body = $this->getResponseJson($result);
        $entryId = (int) $body['data']['id'];

        // Assert block instances and translations were auto-initialized
        $instances = $db->table('cms_block_instances')
            ->where('owner_type', 'entry')
            ->where('owner_id', $entryId)
            ->get()
            ->getResultArray();

        $this->assertCount(1, $instances);
        $instanceId = (int) $instances[0]['id'];
        $this->assertSame($this->activeBlockId, (int) $instances[0]['block_id']);
        $this->assertSame(['theme' => 'light'], json_decode((string) $instances[0]['block_config'], true));

        $translations = $db->table('cms_block_instance_translations')
            ->where('instance_id', $instanceId)
            ->get()
            ->getResultArray();

        // 2 active languages seeded -> 2 translations
        $this->assertCount(2, $translations);
    }

    /**
     * TEM-010: entries of a collection with a multi-block template (the real
     * shape for content types like TeatroMuseo's Exposiciones/Eventos, not
     * just the single-block demo case above) must get every block
     * initialized in the template's declared `sort_order`, regardless of the
     * order blocks appear in the `blocks` array.
     */
    public function testAutoInitializeMultipleBlocksPreservesSortOrder(): void
    {
        $db = Database::connect();
        $template = [
            'version' => '1.0',
            'blocks' => [
                // Declared out of sort_order to prove ordering comes from
                // sort_order, not array position.
                [
                    'block_key' => 'image',
                    'sort_order' => 2,
                    'required' => false,
                    'locked' => false,
                    'block_config_defaults' => [],
                ],
                [
                    'block_key' => 'rich_text',
                    'sort_order' => 1,
                    'required' => true,
                    'locked' => true,
                    'block_config_defaults' => [],
                ],
            ],
        ];

        $db->table('cms_collections')->insert([
            'collection_type' => 'post',
            'collection_key' => 'exhibits',
            'is_active' => 1,
            'requires_approval' => 0,
            'enables_categories' => 0,
            'enables_tags' => 0,
            'sort_order' => 4,
            'block_template' => json_encode($template),
        ]);
        $collectionId = (int) $db->insertID();

        $payload = [
            'collection_id' => (string) $collectionId,
            'workflow_status' => 'published',
            'is_featured' => 'false',
            'translations' => [
                [
                    'language_id' => (string) $this->langEsId,
                    'slug' => 'exhibit-1',
                    'title' => 'Exhibit 1',
                ],
            ],
        ];

        $result = $this->post('/api/v1/cms/entries', $payload);
        $result->assertStatus(200);

        $body = $this->getResponseJson($result);
        $entryId = (int) $body['data']['id'];

        $instances = $db->table('cms_block_instances')
            ->where('owner_type', 'entry')
            ->where('owner_id', $entryId)
            ->orderBy('sort_order', 'ASC')
            ->get()
            ->getResultArray();

        $this->assertCount(2, $instances);
        $this->assertSame(1, (int) $instances[0]['sort_order']);
        $this->assertSame($this->activeBlockId, (int) $instances[0]['block_id']);
        $this->assertSame(2, (int) $instances[1]['sort_order']);
        $this->assertSame($this->secondActiveBlockId, (int) $instances[1]['block_id']);

        $translations = $db->table('cms_block_instance_translations')
            ->whereIn('instance_id', array_column($instances, 'id'))
            ->get()
            ->getResultArray();

        // 2 blocks x 2 active languages -> 4 translations
        $this->assertCount(4, $translations);
    }

    public function testDeleteLockedBlockReturns403(): void
    {
        $db = Database::connect();
        // Insert collection with locked block template
        $template = [
            'version' => '1.0',
            'blocks' => [
                [
                    'block_key' => 'rich_text',
                    'sort_order' => 1,
                    'required' => true,
                    'locked' => true,
                    'block_config_defaults' => [],
                ],
            ],
        ];

        $db->table('cms_collections')->insert([
            'collection_type' => 'post',
            'collection_key' => 'locked_col',
            'is_active' => 1,
            'requires_approval' => 0,
            'sort_order' => 4,
            'block_template' => json_encode($template),
        ]);
        $collectionId = (int) $db->insertID();

        // Create entry -> auto-initializes the locked block
        $db->table('cms_entries')->insert([
            'collection_id' => $collectionId,
            'workflow_status' => 'draft',
            'is_featured' => 0,
            'view_count' => 0,
            'sort_order' => 1,
        ]);
        $entryId = (int) $db->insertID();

        $db->table('cms_block_instances')->insert([
            'block_id' => $this->activeBlockId,
            'owner_type' => 'entry',
            'owner_id' => $entryId,
            'sort_order' => 1,
            'is_active' => 1,
        ]);
        $instanceId = (int) $db->insertID();

        // Try deleting the locked block using nested entry blocks route
        $result = $this->delete("/api/v1/cms/entries/{$entryId}/blocks/{$instanceId}");
        $result->assertStatus(403);

        $body = $this->getResponseJson($result);
        $this->assertSame('error', $body['status']);
        $this->assertStringContainsString('locked', strtolower($body['message']));
    }

    public function testDeleteUnlockedBlockSucceeds(): void
    {
        $db = Database::connect();
        // Insert collection with unlocked block template
        $template = [
            'version' => '1.0',
            'blocks' => [
                [
                    'block_key' => 'rich_text',
                    'sort_order' => 1,
                    'required' => true,
                    'locked' => false,
                    'block_config_defaults' => [],
                ],
            ],
        ];

        $db->table('cms_collections')->insert([
            'collection_type' => 'post',
            'collection_key' => 'unlocked_col',
            'is_active' => 1,
            'requires_approval' => 0,
            'sort_order' => 5,
            'block_template' => json_encode($template),
        ]);
        $collectionId = (int) $db->insertID();

        $db->table('cms_entries')->insert([
            'collection_id' => $collectionId,
            'workflow_status' => 'draft',
            'is_featured' => 0,
            'view_count' => 0,
            'sort_order' => 1,
        ]);
        $entryId = (int) $db->insertID();

        $db->table('cms_block_instances')->insert([
            'block_id' => $this->activeBlockId,
            'owner_type' => 'entry',
            'owner_id' => $entryId,
            'sort_order' => 1,
            'is_active' => 1,
        ]);
        $instanceId = (int) $db->insertID();

        // Unlocked delete should return 200 Success in dcardenasl ApiController
        $result = $this->delete("/api/v1/cms/entries/{$entryId}/blocks/{$instanceId}");
        $result->assertStatus(200);
    }

    public function testAutoInitFailureRollsBackTransaction(): void
    {
        $db = Database::connect();
        // Insert collection with a template referencing a non-existent/invalid block key
        // This fails at runtime in the initializer despite (somehow) passing collection creation
        $template = [
            'version' => '1.0',
            'blocks' => [
                [
                    'block_key' => 'non_existent_block_type',
                    'sort_order' => 1,
                    'required' => true,
                    'locked' => false,
                    'block_config_defaults' => [],
                ],
            ],
        ];

        $db->table('cms_collections')->insert([
            'collection_type' => 'post',
            'collection_key' => 'bad_template_col',
            'is_active' => 1,
            'requires_approval' => 0,
            'sort_order' => 6,
            'block_template' => json_encode($template),
        ]);
        $collectionId = (int) $db->insertID();

        $payload = [
            'collection_id' => (string) $collectionId,
            'workflow_status' => 'published',
            'is_featured' => 'false',
            'translations' => [
                [
                    'language_id' => (string) $this->langEsId,
                    'slug' => 'bad-concert',
                    'title' => 'Bad Concert',
                ],
            ],
        ];

        // This should throw/fail with 500 error because auto-init runtime fails
        $result = $this->post('/api/v1/cms/entries', $payload);
        $result->assertStatus(500);

        // Verify entry was rolled back and is NOT in database
        $entryExists = $db->table('cms_entries')
            ->where('collection_id', $collectionId)
            ->countAllResults() > 0;

        $this->assertFalse($entryExists, 'Entry should not exist in database due to transaction rollback.');
    }
}
