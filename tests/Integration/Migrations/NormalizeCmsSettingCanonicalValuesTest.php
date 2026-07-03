<?php

declare(strict_types=1);

namespace Tests\Integration\Migrations;

use App\Database\Migrations\NormalizeCmsSettingCanonicalValues;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Config\Database;

/**
 * @internal
 */
final class NormalizeCmsSettingCanonicalValuesTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate = true;
    protected $migrateOnce = true;
    protected $refresh = true;
    protected $namespace = 'App';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedDatabase();
    }

    public function testUpMovesBaseLanguageValueIntoSettingValueAndRemovesDuplicateBaseTranslation(): void
    {
        $migration = new NormalizeCmsSettingCanonicalValues();
        $migration->up();

        $db = Database::connect();
        $setting = $db->table('cms_settings')->where('setting_key', 'site_name')->get()->getRowArray();
        $baseTranslation = $db->table('cms_setting_translations')
            ->where('setting_id', (int) $setting['id'])
            ->where('language_id', 1)
            ->get()
            ->getRowArray();
        $englishTranslation = $db->table('cms_setting_translations')
            ->where('setting_id', (int) $setting['id'])
            ->where('language_id', 2)
            ->get()
            ->getRowArray();

        $this->assertSame('Mi Sitio', $setting['setting_value']);
        $this->assertNull($baseTranslation);
        $this->assertSame('My Site', $englishTranslation['setting_value']);
    }

    private function seedDatabase(): void
    {
        $db = Database::connect();

        $db->query('SET FOREIGN_KEY_CHECKS = 0');
        $db->query("DELETE FROM `cms_setting_translations`");
        $db->query("DELETE FROM `cms_settings`");
        $db->query("DELETE FROM `cms_languages`");
        $db->query('SET FOREIGN_KEY_CHECKS = 1');

        $db->table('cms_languages')->insert([
            'id'          => 1,
            'code'        => 'es',
            'name'        => 'Spanish',
            'native_name' => 'Español',
            'is_default'  => 1,
            'is_active'   => 1,
        ]);

        $db->table('cms_languages')->insert([
            'id'          => 2,
            'code'        => 'en',
            'name'        => 'English',
            'native_name' => 'English',
            'is_default'  => 0,
            'is_active'   => 1,
        ]);

        $db->table('cms_settings')->insert([
            'id'              => 1,
            'setting_key'     => 'site_name',
            'setting_value'   => '',
            'setting_type'    => 'string',
            'setting_group'   => 'identity',
            'is_translatable' => 1,
            'sort_order'      => 10,
            'description'     => 'Nombre del sitio / marca',
        ]);

        $db->table('cms_setting_translations')->insert([
            'setting_id'    => 1,
            'language_id'   => 1,
            'setting_value' => 'Mi Sitio',
        ]);

        $db->table('cms_setting_translations')->insert([
            'setting_id'    => 1,
            'language_id'   => 2,
            'setting_value' => 'My Site',
        ]);
    }
}
