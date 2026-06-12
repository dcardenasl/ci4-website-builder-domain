<?php

declare(strict_types=1);

namespace Tests\Integration\Models;

use App\Models\CollectionModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * Smoke tests for CollectionModel. Extend with persistence scenarios as
 * domain behavior solidifies.
 *
 * @internal
 */
final class CollectionModelTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = true;
    protected $refresh     = true;
    protected $namespace   = 'App';

    public function testModelReportsCorrectTable(): void
    {
        $model = new CollectionModel();

        $this->assertSame('cms_collections', $model->getTable());
    }
}
