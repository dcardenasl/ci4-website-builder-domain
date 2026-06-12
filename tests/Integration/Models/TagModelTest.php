<?php

declare(strict_types=1);

namespace Tests\Integration\Models;

use App\Models\TagModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * Smoke tests for TagModel. Extend with persistence scenarios as
 * domain behavior solidifies.
 *
 * @internal
 */
final class TagModelTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = true;
    protected $refresh     = true;
    protected $namespace   = 'App';

    public function testModelReportsCorrectTable(): void
    {
        $model = new TagModel();

        $this->assertSame('cms_tags', $model->getTable());
    }
}
