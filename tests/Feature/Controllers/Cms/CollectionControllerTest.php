<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Cms;

use App\Libraries\Hub\HubClient;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;
use Config\Services;
use dcardenasl\Ci4ApiCore\Http\Client\IntrospectResult;

/**
 * HTTP smoke test for CollectionController. The configured route group
 * wraps every endpoint in an auth filter — an unauthenticated request returns 401 — a sufficient signal that the route was registered and wired.
 *
 * Extend with authenticated 200 flows as business rules solidify.
 *
 * @internal
 */
final class CollectionControllerTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = true;
    protected $refresh     = true;
    protected $namespace   = 'App';

    protected function tearDown(): void
    {
        Services::reset();
        // ContextHolder is a static per-process registry (not a Service instance), so
        // Services::reset() alone doesn't clear the SecurityContext DomainAuthFilter set for the
        // mocked request above — without this, the next unauthenticated-request test in this
        // process still sees a stale "authenticated" context and gets 200/403 instead of 401.
        \dcardenasl\Ci4ApiCore\Http\ContextHolder::flush();
        parent::tearDown();
    }

    public function testIndexSmoke(): void
    {
        $result = $this->get('/api/v1/cms/collections');

        $result->assertStatus(401);
    }

    public function testShowNotFound(): void
    {
        $result = $this->get('/api/v1/cms/collections/99999');

        $result->assertStatus(401);
    }

    public function testCheckSlugRequiresAuth(): void
    {
        $result = $this->get('/api/v1/cms/collections/check-slug?slug=news&language_id=1');

        $result->assertStatus(401);
    }

    /**
     * Regression for the COL-001 bug found while adding a `collection_type` editor to the
     * admin: CollectionResponseDTO never exposed `collection_type` at all, so the admin's edit
     * form always rendered it empty and `updateStructure()`'s "keep current value" fallback
     * silently reset it to 'other' on every save that didn't post `wizard_config.type`.
     */
    public function testShowExposesCollectionType(): void
    {
        $stub = new class (new IntrospectResult(
            valid: true,
            uid: 1,
            permissions: ['cms.collections.read'],
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

        $db = Database::connect();
        $db->table('cms_collections')->insert([
            'collection_key' => 'eventos',
            'collection_type' => 'eventos',
            'is_active' => 1,
        ]);
        $collectionId = (int) $db->insertID();

        $result = $this->withHeaders(['Authorization' => 'Bearer fake-test-token'])
            ->get('/api/v1/cms/collections/' . $collectionId);

        $result->assertStatus(200);
        $body = json_decode($result->getJSON(), true);

        $this->assertSame('eventos', $body['data']['collection_type']);
    }
}
