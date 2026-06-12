<?php

declare(strict_types=1);

namespace Tests\Integration\Models;

use App\Models\MenuItemModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * Smoke tests for MenuItemModel. Extend with persistence scenarios as
 * domain behavior solidifies.
 *
 * @internal
 */
final class MenuItemModelTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = true;
    protected $refresh     = true;
    protected $namespace   = 'App';

    public function testModelReportsCorrectTable(): void
    {
        $model = new MenuItemModel();

        $this->assertSame('cms_menu_items', $model->getTable());
    }
}
