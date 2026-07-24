<?php

declare(strict_types=1);

namespace Tests\Integration\Models;

use App\Models\PageModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * Smoke tests for PageModel. Extend with persistence scenarios as
 * domain behavior solidifies.
 *
 * @internal
 */
final class PageModelTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = true;
    protected $refresh     = true;
    protected $namespace   = 'App';

    public function testModelReportsCorrectTable(): void
    {
        $model = new PageModel();

        $this->assertSame('cms_pages', $model->getTable());
    }
}
