<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Cms;

use App\Libraries\Cms\CacheInvalidationClient;
use App\Libraries\Hub\HubClient;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;
use Config\Services;
use dcardenasl\Ci4ApiCore\Http\Client\IntrospectResult;

/** @internal */
final class SortOrderControllerTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate = true;
    protected $migrateOnce = true;
    protected $refresh = true;
    protected $namespace = 'App';

    protected function tearDown(): void
    {
        Services::reset();
        \dcardenasl\Ci4ApiCore\Http\ContextHolder::flush();
        parent::tearDown();
    }

    public function testReorderRequiresAuthentication(): void
    {
        $result = $this->withBody('{"resource":"collections","items":[{"id":1,"sort_order":0}]}')
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post('/api/v1/cms/collections/sort-orders');

        $result->assertStatus(401);
    }

    public function testReorderUpdatesTheWholeBatchAtomically(): void
    {
        Services::injectMock('hubClient', new class (new IntrospectResult(
            valid: true,
            uid: 1,
            permissions: ['cms.collections.write'],
            exp: time() + 3600,
            error: null,
        )) extends HubClient {
            public function __construct(private readonly IntrospectResult $result)
            {
            }

            public function introspect(string $token): IntrospectResult
            {
                return $this->result;
            }
        });
        Services::injectMock('cacheInvalidationClient', $this->createMock(CacheInvalidationClient::class));

        $db = Database::connect();
        $db->table('cms_collections')->insert(['collection_key' => 'first', 'sort_order' => 10]);
        $firstId = (int) $db->insertID();
        $db->table('cms_collections')->insert(['collection_key' => 'second', 'sort_order' => 20]);
        $secondId = (int) $db->insertID();

        $result = $this->withHeaders([
            'Authorization' => 'Bearer fake-test-token',
            'Content-Type' => 'application/json',
        ])->withBody(json_encode([
            'resource' => 'collections',
            'items' => [
                ['id' => $secondId, 'sort_order' => 0],
                ['id' => $firstId, 'sort_order' => 1],
            ],
        ], JSON_THROW_ON_ERROR))->post('/api/v1/cms/collections/sort-orders');

        $result->assertStatus(200);
        $this->assertSame(1, (int) $db->table('cms_collections')->where('id', $firstId)->get()->getRow()->sort_order);
        $this->assertSame(0, (int) $db->table('cms_collections')->where('id', $secondId)->get()->getRow()->sort_order);
    }
}
