<?php

declare(strict_types=1);

namespace Tests\Integration\Models;

use App\Models\CategoryModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * Smoke tests for CategoryModel. Extend with persistence scenarios as
 * domain behavior solidifies.
 *
 * @internal
 */
final class CategoryModelTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = true;
    protected $refresh     = true;
    protected $namespace   = 'App';

    public function testModelReportsCorrectTable(): void
    {
        $model = new CategoryModel();

        $this->assertSame('cms_categories', $model->getTable());
    }

    public function testModelAllowsRelationFilters(): void
    {
        $model = new CategoryModel();

        $this->assertContains('collection_id', $model->getFilterableFields());
        $this->assertContains('parent_id', $model->getFilterableFields());
        $this->assertContains('is_active', $model->getFilterableFields());
    }
}
