<?php

declare(strict_types=1);

namespace Tests\Integration\Models;

use App\Models\MenuModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * Smoke tests for MenuModel. Extend with persistence scenarios as
 * domain behavior solidifies.
 *
 * @internal
 */
final class MenuModelTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = true;
    protected $refresh     = true;
    protected $namespace   = 'App';

    public function testModelReportsCorrectTable(): void
    {
        $model = new MenuModel();

        $this->assertSame('cms_menus', $model->getTable());
    }
}
