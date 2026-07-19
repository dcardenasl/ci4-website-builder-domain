<?php

declare(strict_types=1);

namespace Tests\Integration\Services\Cms;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Config\Services;
use dcardenasl\Ci4ApiCore\Exceptions\ValidationException;

/**
 * Regression coverage for `SettingService::batchUpdate()` after replacing its
 * hand-rolled `Database::connect()` + transStart/transComplete/transRollback
 * block with the inherited `wrapInTransaction()` helper (2026-07-19). The
 * behavior under test — atomicity across the whole batch — was previously
 * unverified by any test.
 *
 * @internal
 */
final class SettingServiceBatchUpdateTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = true;
    protected $refresh     = true;
    protected $namespace   = 'App';

    private int $settingOneId;

    private int $settingTwoId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->db->disableForeignKeyChecks();
        $this->db->query("DELETE FROM `cms_setting_translations`");
        $this->db->query("DELETE FROM `cms_settings`");
        $this->db->enableForeignKeyChecks();

        $this->db->table('cms_settings')->insert([
            'setting_key'   => 'batch_test_one',
            'setting_value' => 'original-one',
            'setting_type'  => 'string',
        ]);
        $this->settingOneId = (int) $this->db->insertID();

        $this->db->table('cms_settings')->insert([
            'setting_key'   => 'batch_test_two',
            'setting_value' => 'original-two',
            'setting_type'  => 'string',
        ]);
        $this->settingTwoId = (int) $this->db->insertID();
    }

    protected function tearDown(): void
    {
        Services::reset();
        parent::tearDown();
    }

    public function testBatchUpdatePersistsAllItemsOnSuccess(): void
    {
        $service = Services::settingService(false);

        $result = $service->batchUpdate([
            ['id' => $this->settingOneId, 'payload' => ['setting_value' => 'updated-one']],
            ['id' => $this->settingTwoId, 'payload' => ['setting_value' => 'updated-two']],
        ]);

        $this->assertSame([$this->settingOneId, $this->settingTwoId], $result['updated']);

        $this->assertSame(
            'updated-one',
            $this->db->table('cms_settings')->getWhere(['id' => $this->settingOneId])->getRowArray()['setting_value'] ?? null
        );
        $this->assertSame(
            'updated-two',
            $this->db->table('cms_settings')->getWhere(['id' => $this->settingTwoId])->getRowArray()['setting_value'] ?? null
        );
    }

    public function testBatchUpdateRollsBackAllItemsWhenOneFails(): void
    {
        $service = Services::settingService(false);

        try {
            $service->batchUpdate([
                ['id' => $this->settingOneId, 'payload' => ['setting_value' => 'should-not-persist']],
                // Invalid: setting_type is not in the DTO's in_list rule.
                ['id' => $this->settingTwoId, 'payload' => ['setting_type' => 'not-a-real-type']],
            ]);
            $this->fail('Expected a ValidationException to abort the batch.');
        } catch (ValidationException) {
            // Expected.
        }

        // Neither item's changes must have persisted — the first item's
        // successful write must be rolled back by the second item's failure.
        $this->assertSame(
            'original-one',
            $this->db->table('cms_settings')->getWhere(['id' => $this->settingOneId])->getRowArray()['setting_value'] ?? null
        );
        $this->assertSame(
            'string',
            $this->db->table('cms_settings')->getWhere(['id' => $this->settingTwoId])->getRowArray()['setting_type'] ?? null
        );
    }
}
