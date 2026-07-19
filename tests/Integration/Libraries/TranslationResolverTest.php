<?php

declare(strict_types=1);

namespace Tests\Integration\Libraries;

use App\Libraries\Cms\TranslationResolver;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Config\Database;
use Tests\Support\Fixtures\CmsFixtureFactory;

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

    private CmsFixtureFactory $fixtures;

    /** @var list<array{id:int,code:string,name:string,is_default:bool}> */
    private array $languages;

    private int $settingId;

    /** @var array{id:int,key:string,translations:list<array<string,mixed>>} */
    private array $collection;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = new TranslationResolver(service('fileUrlResolver'));
        $this->seedDatabase();
    }

    private function seedDatabase(): void
    {
        $db = Database::connect();
        $db->disableForeignKeyChecks();
        $db->query("DELETE FROM `cms_collection_translations`");
        $db->query("DELETE FROM `cms_collections`");
        $db->query("DELETE FROM `cms_setting_translations`");
        $db->query("DELETE FROM `cms_settings`");
        $db->query("DELETE FROM `cms_languages`");
        $db->enableForeignKeyChecks();

        $this->fixtures = new CmsFixtureFactory($db, self::class);
        $this->languages = $this->fixtures->languages(3);
        $db->table('cms_languages')
            ->where('id', $this->languages[2]['id'])
            ->update(['is_active' => 0]);

        $settingKey = $this->fixtures->slug('setting-key');
        $db->table('cms_settings')->insert([
            'setting_key' => $settingKey,
            'setting_value' => $this->fixtures->text('setting-default'),
            'setting_type' => 'string',
            'setting_group' => 'general',
            'is_translatable' => 1,
        ]);
        $this->settingId = (int) $db->insertID();

        $settingValues = [];
        foreach ([$this->languages[0], $this->languages[1]] as $language) {
            $settingValues[$language['code']] = $this->fixtures->text('setting-value', $language['code']);
            $db->table('cms_setting_translations')->insert([
                'setting_id' => $this->settingId,
                'language_id' => $language['id'],
                'setting_value' => $settingValues[$language['code']],
            ]);
        }

        $collectionTranslations = [];
        foreach ([$this->languages[0], $this->languages[1]] as $language) {
            $collectionTranslations[] = [
                'language_id' => $language['id'],
                'slug' => $this->fixtures->slug('collection-slug', $language['code']),
                'name' => $this->fixtures->text('collection-name', $language['code']),
                'description' => $this->fixtures->text('collection-description', $language['code']),
                'listing_title' => $this->fixtures->text('collection-listing', $language['code']),
            ];
        }
        $this->collection = $this->fixtures->collection($collectionTranslations);

        $this->settingValues = $settingValues;
    }

    /** @var array<string, string> */
    private array $settingValues;

    public function testResolveHappyPath(): void
    {
        $language = $this->languages[1];
        $result = $this->resolver->resolve('setting', $this->settingId, $language['code']);

        $this->assertSame($this->settingValues[$language['code']], $result['setting_value']);
        $this->assertFalse($result['is_fallback']);
    }

    public function testResolveFallbackToDefaultWhenLanguageInactive(): void
    {
        $result = $this->resolver->resolve('setting', $this->settingId, $this->languages[2]['code']);

        $this->assertSame($this->settingValues[$this->languages[0]['code']], $result['setting_value']);
        $this->assertTrue($result['is_fallback']);
    }

    public function testResolveFallbackToDefaultWhenLanguageNotExists(): void
    {
        $unknownLocale = $this->fixtures->slug('unknown-locale');
        $result = $this->resolver->resolve('setting', $this->settingId, $unknownLocale);

        $this->assertSame($this->settingValues[$this->languages[0]['code']], $result['setting_value']);
        $this->assertTrue($result['is_fallback']);
    }

    public function testResolveUnsupportedResourceTypeThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->resolver->resolve('unsupported_type', $this->settingId, $this->languages[0]['code']);
    }

    public function testResolveCollectionIncludesSlugForLanguage(): void
    {
        $language = $this->languages[0];
        $translation = $this->collection['translations'][0];
        $result = $this->resolver->resolve('collection', $this->collection['id'], $language['code']);

        $this->assertSame($translation['slug'], $result['slug']);
        $this->assertSame($translation['name'], $result['name']);
        $this->assertFalse($result['is_fallback']);
    }
}
