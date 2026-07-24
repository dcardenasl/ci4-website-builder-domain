<?php

declare(strict_types=1);

namespace Tests\Integration\Models;

use App\Models\RedirectModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * Smoke tests for RedirectModel. Extend with persistence scenarios as
 * domain behavior solidifies.
 *
 * @internal
 */
final class RedirectModelTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = true;
    protected $refresh     = true;
    protected $namespace   = 'App';

    public function testModelReportsCorrectTable(): void
    {
        $model = new RedirectModel();

        $this->assertSame('cms_redirects', $model->getTable());
    }
}
