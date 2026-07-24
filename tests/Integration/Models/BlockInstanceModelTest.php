<?php

declare(strict_types=1);

namespace Tests\Integration\Models;

use App\Models\BlockInstanceModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * Smoke tests for BlockInstanceModel. Extend with persistence scenarios as
 * domain behavior solidifies.
 *
 * @internal
 */
final class BlockInstanceModelTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = true;
    protected $refresh     = true;
    protected $namespace   = 'App';

    public function testModelReportsCorrectTable(): void
    {
        $model = new BlockInstanceModel();

        $this->assertSame('cms_block_instances', $model->getTable());
    }
}
