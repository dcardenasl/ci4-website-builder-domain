<?php

declare(strict_types=1);

namespace Tests\Integration\Models;

use App\Models\BlockTypeModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * Smoke tests for BlockTypeModel. Extend with persistence scenarios as
 * domain behavior solidifies.
 *
 * @internal
 */
final class BlockTypeModelTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = true;
    protected $refresh     = true;
    protected $namespace   = 'App';

    public function testModelReportsCorrectTable(): void
    {
        $model = new BlockTypeModel();

        $this->assertSame('cms_content_blocks', $model->getTable());
    }

    public function testCmsContentBlocksHasFulltextSearchIndex(): void
    {
        $db = db_connect();

        $rows = $db->query(
            "SHOW INDEX FROM cms_content_blocks WHERE Key_name = 'ft_cms_content_blocks_search'"
        )->getResultArray();

        $this->assertNotEmpty($rows);
        $this->assertSame('FULLTEXT', $rows[0]['Index_type'] ?? null);
    }
}
