<?php

declare(strict_types=1);

namespace Tests\Integration\Validators;

use App\Exceptions\BlockTemplateValidationException;
use App\Validators\BlockTemplateValidator;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Config\Database;

/**
 * @internal
 */
final class BlockTemplateValidatorTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = true;
    protected $refresh     = true;
    protected $namespace   = 'App';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedBlockTypes();
    }

    private function seedBlockTypes(): void
    {
        $db = Database::connect();
        $db->query('SET FOREIGN_KEY_CHECKS = 0');
        $db->query("DELETE FROM `cms_content_blocks`");
        $db->query('SET FOREIGN_KEY_CHECKS = 1');

        $db->table('cms_content_blocks')->insert([
            'id' => 1,
            'block_key' => 'rich_text',
            'name' => 'Rich Text',
            'description' => null,
            'category' => 'content',
            'icon' => 'align-left',
            'schema_definition' => '{}',
            'supports_pages' => 1,
            'supports_entries' => 1,
            'is_container' => 0,
            'is_active' => 1,
            'sort_order' => 1,
        ]);

        $db->table('cms_content_blocks')->insert([
            'id' => 2,
            'block_key' => 'archived_block',
            'name' => 'Archived',
            'description' => null,
            'category' => 'content',
            'icon' => 'ban',
            'schema_definition' => '{}',
            'supports_pages' => 1,
            'supports_entries' => 1,
            'is_container' => 0,
            'is_active' => 0,
            'sort_order' => 2,
        ]);
    }

    public function testValidatorAcceptsActiveBlockAndRejectsInactiveBlock(): void
    {
        $validator = new BlockTemplateValidator();
        $validator->validate([
            'version' => '1.0',
            'blocks' => [
                [
                    'block_key' => 'rich_text',
                    'sort_order' => 1,
                    'required' => true,
                    'locked' => false,
                    'block_config_defaults' => [],
                ],
            ],
        ]);

        $this->expectException(BlockTemplateValidationException::class);

        $validator->validate([
            'version' => '1.0',
            'blocks' => [
                [
                    'block_key' => 'archived_block',
                    'sort_order' => 1,
                    'required' => true,
                    'locked' => false,
                    'block_config_defaults' => [],
                ],
            ],
        ]);
    }
}
