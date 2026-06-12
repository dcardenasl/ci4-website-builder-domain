<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Cms;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * HTTP smoke test for BlockTypeController. The configured route group
 * wraps every endpoint in an auth filter — an unauthenticated request returns 401 — a sufficient signal that the route was registered and wired.
 *
 * Extend with authenticated 200 flows as business rules solidify.
 *
 * @internal
 */
final class BlockTypeControllerTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = true;
    protected $refresh     = true;
    protected $namespace   = 'App';

    public function testIndexSmoke(): void
    {
        $result = $this->get('/api/v1/cms/block-types');

        $result->assertStatus(401);
    }

    public function testShowNotFound(): void
    {
        $result = $this->get('/api/v1/cms/block-types/99999');

        $result->assertStatus(401);
    }
}
