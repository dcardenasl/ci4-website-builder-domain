<?php

declare(strict_types=1);

namespace Tests\Integration\Services\Cms;

use App\DTO\Request\Cms\BlockInstanceUpdateRequestDTO;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Config\Database;
use Config\Services;

/**
 * @internal
 */
final class BlockInstanceServiceTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = true;
    protected $refresh     = true;
    protected $namespace   = 'App';

    protected function tearDown(): void
    {
        Services::reset();
        parent::tearDown();
    }

    public function testUpdatePersistsBlockConfigAsJson(): void
    {
        $db = Database::connect();

        $db->query('SET FOREIGN_KEY_CHECKS = 0');
        $db->table('cms_block_instance_translations')->truncate();
        $db->table('cms_block_instances')->truncate();
        $db->table('cms_content_blocks')->truncate();
        $db->query('SET FOREIGN_KEY_CHECKS = 1');

        $db->table('cms_content_blocks')->insert([
            'id' => 5,
            'block_key' => 'hero',
            'name' => 'Hero',
            'schema_definition' => json_encode(['fields' => []]),
            'supports_pages' => 1,
            'supports_entries' => 1,
            'is_container' => 0,
            'is_active' => 1,
            'sort_order' => 1,
        ]);

        $db->table('cms_block_instances')->insert([
            'id' => 10,
            'block_id' => 5,
            'owner_type' => 'page',
            'owner_id' => 21,
            'sort_order' => 1,
            'is_active' => 1,
        ]);

        $dto = $this->hydrateDto(BlockInstanceUpdateRequestDTO::class, [
            'block_config' => ['theme' => 'dark'],
        ]);

        $service = Services::blockInstanceService(false);
        $result = $service->update(10, $dto, null);

        $this->assertSame(['theme' => 'dark'], $result->toArray()['block_config']);
        $stored = $db->table('cms_block_instances')->getWhere(['id' => 10])->getRowArray()['block_config'];
        $this->assertSame(['theme' => 'dark'], json_decode((string) $stored, true));
    }

    /**
     * @template T of object
     * @param class-string<T> $class
     * @param array<string, mixed> $data
     * @return T
     */
    private function hydrateDto(string $class, array $data): object
    {
        $reflection = new \ReflectionClass($class);
        /** @var object $dto */
        $dto = $reflection->newInstanceWithoutConstructor();

        $method = $reflection->getMethod('map');
        $method->setAccessible(true);
        $method->invoke($dto, $data);

        return $dto;
    }
}
