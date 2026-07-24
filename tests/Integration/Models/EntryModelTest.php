<?php

declare(strict_types=1);

namespace Tests\Integration\Models;

use App\Models\EntryModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * Smoke tests for EntryModel. Extend with persistence scenarios as
 * domain behavior solidifies.
 *
 * @internal
 */
final class EntryModelTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = true;
    protected $refresh     = true;
    protected $namespace   = 'App';

    public function testModelReportsCorrectTable(): void
    {
        $model = new EntryModel();

        $this->assertSame('cms_entries', $model->getTable());
    }
}
