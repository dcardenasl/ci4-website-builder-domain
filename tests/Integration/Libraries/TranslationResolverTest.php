<?php

declare(strict_types=1);

namespace Tests\Integration\Libraries;

use App\Libraries\Cms\TranslationResolver;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Config\Database;

/**
 * @internal
 */
final class TranslationResolverTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = true;
    protected $refresh     = true;
    protected $namespace   = 'App';

    private TranslationResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = new TranslationResolver(service('fileUrlResolver'));
        $this->seedDatabase();
    }

    private function seedDatabase(): void
    {
        $db = Database::connect();

        // Clean tables
        $db->query('SET FOREIGN_KEY_CHECKS = 0');
        $db->query("DELETE FROM `cms_collection_translations`");
        $db->query("DELETE FROM `cms_collections`");
        $db->query("DELETE FROM `cms_setting_translations`");
        $db->query("DELETE FROM `cms_settings`");
        $db->query("DELETE FROM `cms_languages`");
        $db->query('SET FOREIGN_KEY_CHECKS = 1');

        // 1. Insert Languages
        // English (Default, Active)
        $db->table('cms_languages')->insert([
            'id'          => 1,
            'code'        => 'en',
            'name'        => 'English',
            'native_name' => 'English',
            'is_default'  => 1,
            'is_active'   => 1,
        ]);

        // Spanish (Active)
        $db->table('cms_languages')->insert([
            'id'          => 2,
            'code'        => 'es',
            'name'        => 'Spanish',
            'native_name' => 'Español',
            'is_default'  => 0,
            'is_active'   => 1,
        ]);

        // French (Inactive)
        $db->table('cms_languages')->insert([
            'id'          => 3,
            'code'        => 'fr',
            'name'        => 'French',
            'native_name' => 'Français',
            'is_default'  => 0,
            'is_active'   => 0,
        ]);

        // 2. Insert Settings
        $db->table('cms_settings')->insert([
            'id'              => 1,
            'setting_key'     => 'site_name',
            'setting_value'   => 'Default Site Name',
            'setting_type'    => 'string',
            'setting_group'   => 'general',
            'is_translatable' => 1,
        ]);

        // 3. Insert Collection
        $db->table('cms_collections')->insert([
            'id'                       => 1,
            'collection_key'           => 'noticias',
            'is_active'                => 1,
            'requires_approval'        => 0,
            'enables_categories'       => 1,
            'enables_tags'             => 1,
            'default_sitemap_priority' => '0.70',
            'default_changefreq'       => 'weekly',
            'sort_order'               => 10,
        ]);

        // 4. Insert Setting Translations
        // English translation
        $db->table('cms_setting_translations')->insert([
            'setting_id'    => 1,
            'language_id'   => 1,
            'setting_value' => 'My English Site Name',
        ]);

        // Spanish translation
        $db->table('cms_setting_translations')->insert([
            'setting_id'    => 1,
            'language_id'   => 2,
            'setting_value' => 'Nombre de mi Sitio en Español',
        ]);

        // 5. Insert Collection Translations
        $db->table('cms_collection_translations')->insert([
            'collection_id' => 1,
            'language_id'   => 1,
            'slug'          => 'news',
            'name'          => 'News',
            'description'   => 'News and current events section.',
            'listing_title' => 'Latest News',
        ]);

        $db->table('cms_collection_translations')->insert([
            'collection_id' => 1,
            'language_id'   => 2,
            'slug'          => 'noticias',
            'name'          => 'Noticias',
            'description'   => 'Sección de noticias y actualidad.',
            'listing_title' => 'Últimas Noticias',
        ]);
    }

    public function testResolveHappyPath(): void
    {
        $result = $this->resolver->resolve('setting', 1, 'es');

        $this->assertSame('Nombre de mi Sitio en Español', $result['setting_value']);
        $this->assertFalse($result['is_fallback']);
    }

    public function testResolveFallbackToDefaultWhenLanguageInactive(): void
    {
        $result = $this->resolver->resolve('setting', 1, 'fr'); // French is inactive

        $this->assertSame('My English Site Name', $result['setting_value']);
        $this->assertTrue($result['is_fallback']);
    }

    public function testResolveFallbackToDefaultWhenLanguageNotExists(): void
    {
        $result = $this->resolver->resolve('setting', 1, 'de'); // German does not exist

        $this->assertSame('My English Site Name', $result['setting_value']);
        $this->assertTrue($result['is_fallback']);
    }

    public function testResolveUnsupportedResourceTypeThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->resolver->resolve('unsupported_type', 1, 'es');
    }

    public function testResolveCollectionIncludesSlugForLanguage(): void
    {
        $result = $this->resolver->resolve('collection', 1, 'en');

        $this->assertSame('news', $result['slug']);
        $this->assertSame('News', $result['name']);
        $this->assertFalse($result['is_fallback']);
    }
}
