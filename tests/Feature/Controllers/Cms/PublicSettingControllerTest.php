<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Cms;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\Fixtures\CmsFixtureFactory;
use Tests\Support\Traits\WithWebAppKeyTrait;

/**
 * Characterization coverage for PublicSettingController::index(), written
 * before moving its model()/translation-resolution logic into
 * SettingService::listPublic() (DOM-117) — locks in the current response
 * shape (plain setting_key => value map, translation fallback, file_id
 * special-casing) so the refactor cannot silently change it.
 *
 * @internal
 */
final class PublicSettingControllerTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;
    use WithWebAppKeyTrait;

    protected $migrate     = true;
    protected $migrateOnce = true;
    protected $refresh     = true;
    protected $namespace   = 'App';

    /** @var list<array{id:int,code:string,name:string,is_default:bool}> */
    private array $languages;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configureWebAppKey();

        $this->db->disableForeignKeyChecks();
        $this->db->query('DELETE FROM `cms_setting_translations`');
        $this->db->query('DELETE FROM `cms_settings`');
        $this->db->query('DELETE FROM `cms_languages`');
        $this->db->enableForeignKeyChecks();

        $this->languages = (new CmsFixtureFactory($this->db, self::class))->languages(2);
    }

    protected function tearDown(): void
    {
        $this->restoreWebAppKey();
        parent::tearDown();
    }

    private function insertSetting(array $overrides = []): int
    {
        $this->db->table('cms_settings')->insert(array_replace([
            'setting_key'     => 'site_name_' . uniqid(),
            'setting_value'   => 'default value',
            'setting_type'    => 'string',
            'input_type'      => 'text',
            'is_public'       => 1,
            'is_active'       => 1,
            'is_translatable' => 0,
        ], $overrides));

        return (int) $this->db->insertID();
    }

    public function testReturnsPlainValueForNonTranslatableSetting(): void
    {
        $this->insertSetting(['setting_key' => 'plain_setting', 'setting_value' => 'hello']);

        $result = $this->get('api/v1/public/settings');

        $result->assertStatus(200);
        $body = json_decode((string) $result->response()->getBody(), true);

        $this->assertSame('success', $body['status']);
        $this->assertSame('hello', $body['data']['plain_setting']);
    }

    public function testResolvesTranslationForTranslatableSetting(): void
    {
        $settingId = $this->insertSetting([
            'setting_key'     => 'translatable_setting',
            'setting_value'   => 'fallback value',
            'is_translatable' => 1,
        ]);

        $defaultLanguageId = $this->languages[0]['id'];
        $this->db->table('cms_setting_translations')->insert([
            'setting_id'    => $settingId,
            'language_id'   => $defaultLanguageId,
            'setting_value' => 'translated value',
        ]);

        $result = $this->get('api/v1/public/settings');

        $result->assertStatus(200);
        $body = json_decode((string) $result->response()->getBody(), true);

        $this->assertSame('translated value', $body['data']['translatable_setting']);
    }

    public function testFileIdSettingShapesValueWithFileMetadata(): void
    {
        $this->insertSetting([
            'setting_key'   => 'logo_setting',
            'setting_type'  => 'file_id',
            'setting_value' => '0',
            'setting_meta'  => json_encode(['url' => 'https://example.test/fallback.png', 'mime_type' => 'image/png']),
        ]);

        $result = $this->get('api/v1/public/settings');

        $result->assertStatus(200);
        $body = json_decode((string) $result->response()->getBody(), true);

        $this->assertSame([
            'file_id'   => 0,
            'url'       => 'https://example.test/fallback.png',
            'mime_type' => 'image/png',
        ], $body['data']['logo_setting']);
    }

    public function testExcludesNonPublicAndInactiveSettings(): void
    {
        $this->insertSetting(['setting_key' => 'private_setting', 'is_public' => 0]);
        $this->insertSetting(['setting_key' => 'inactive_setting', 'is_active' => 0]);
        $this->insertSetting(['setting_key' => 'visible_setting']);

        $result = $this->get('api/v1/public/settings');

        $body = json_decode((string) $result->response()->getBody(), true);

        $this->assertArrayNotHasKey('private_setting', $body['data']);
        $this->assertArrayNotHasKey('inactive_setting', $body['data']);
        $this->assertArrayHasKey('visible_setting', $body['data']);
    }
}
