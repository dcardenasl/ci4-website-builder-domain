<?php

declare(strict_types=1);

namespace Tests\Unit\Validators;

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

    private BlockTemplateValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new BlockTemplateValidator();
        $this->seedBlockTypes();
    }

    private function seedBlockTypes(): void
    {
        $db = Database::connect();
        $db->disableForeignKeyChecks();
        $db->table('cms_content_blocks')->truncate();
        $db->enableForeignKeyChecks();

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

    public function testValidateNullPassesSilently(): void
    {
        $this->validator->validate(null);
        $this->assertTrue(true); // Passes without exception
    }

    public function testValidateValidTemplatePasses(): void
    {
        $template = [
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
        ];

        $this->validator->validate($template);
        $this->assertTrue(true); // Passes without exception
    }

    public function testValidateBadVersionThrowsException(): void
    {
        $template = [
            'version' => '2.0',
            'blocks' => [
                [
                    'block_key' => 'rich_text',
                    'sort_order' => 1,
                    'required' => true,
                    'locked' => false,
                    'block_config_defaults' => [],
                ],
            ],
        ];

        $this->expectException(BlockTemplateValidationException::class);
        $this->expectExceptionMessage('version must be "1.0"');

        $this->validator->validate($template);
    }

    public function testValidateMissingBlockKeyThrowsException(): void
    {
        $template = [
            'version' => '1.0',
            'blocks' => [
                [
                    'sort_order' => 1,
                    'required' => true,
                    'locked' => false,
                    'block_config_defaults' => [],
                ],
            ],
        ];

        $this->expectException(BlockTemplateValidationException::class);
        $this->expectExceptionMessage("block_key is required");

        $this->validator->validate($template);
    }

    public function testValidateInvalidBlockKeyThrowsException(): void
    {
        $template = [
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
        ];

        $this->expectException(BlockTemplateValidationException::class);
        $this->expectExceptionMessage("does not match any active block type");

        $this->validator->validate($template);
    }

    public function testValidateDuplicateSortOrderThrowsException(): void
    {
        $template = [
            'version' => '1.0',
            'blocks' => [
                [
                    'block_key' => 'rich_text',
                    'sort_order' => 1,
                    'required' => true,
                    'locked' => false,
                    'block_config_defaults' => [],
                ],
                [
                    'block_key' => 'rich_text',
                    'sort_order' => 1,
                    'required' => true,
                    'locked' => false,
                    'block_config_defaults' => [],
                ],
            ],
        ];

        $this->expectException(BlockTemplateValidationException::class);
        $this->expectExceptionMessage("Duplicate sort_order 1");

        $this->validator->validate($template);
    }
}
