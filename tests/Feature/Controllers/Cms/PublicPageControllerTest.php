<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Cms;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\Fixtures\CmsFixtureFactory;
use Tests\Support\Traits\WithWebAppKeyTrait;

/**
 * @internal
 */
final class PublicPageControllerTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;
    use WithWebAppKeyTrait;

    protected $migrate     = true;
    protected $migrateOnce = true;
    protected $refresh     = true;
    protected $namespace   = 'App';

    private CmsFixtureFactory $fixtures;

    /** @var list<array{id:int,code:string,name:string,is_default:bool}> */
    private array $languages;

    private string $draftPageSlug;

    private string $draftPageTitle;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configureWebAppKey();

        $this->db->disableForeignKeyChecks();
        $this->db->query("DELETE FROM `cms_page_translations`");
        $this->db->query("DELETE FROM `cms_pages`");
        $this->db->query("DELETE FROM `cms_languages`");
        $this->db->enableForeignKeyChecks();

        $this->fixtures = new CmsFixtureFactory($this->db, self::class);
        $this->languages = $this->fixtures->languages(3);
        $this->draftPageSlug = $this->fixtures->slug('draft-page', $this->languages[0]['code']);
        $this->draftPageTitle = $this->fixtures->text('draft-title', $this->languages[0]['code']);
    }

    protected function tearDown(): void
    {
        $this->restoreWebAppKey();
        parent::tearDown();
    }

    public function testGetPublicPageSuccess(): void
    {
        $translation = $this->pageTranslation(0);
        $this->fixtures->page([$translation]);

        $result = $this->get($this->pagePath($translation['slug']));

        $result->assertStatus(200);

        $body = json_decode($result->getJSON(), true);
        $this->assertSame('success', $body['status']);
        $this->assertSame($translation['slug'], $body['data']['slug']);
        $this->assertSame($translation['title'], $body['data']['title']);
        $this->assertSame($translation['excerpt'], $body['data']['excerpt']);
        $this->assertSame($translation['og_image_url'], $body['data']['og_image']['url']);
        $this->assertSame('external_url', $body['data']['og_image']['source_kind']);
    }

    public function testGetPublicPageNotFound(): void
    {
        $result = $this->get($this->pagePath($this->fixtures->slug('missing')));

        $result->assertStatus(404);
    }

    public function testDraftPageIsNotFoundByDefault(): void
    {
        $this->insertDraftPage();

        $result = $this->get($this->pagePath($this->draftSlug()));

        $result->assertStatus(404);
    }

    public function testDraftPageIsNotFoundWithBarePreviewFlagAndNoSignature(): void
    {
        $this->insertDraftPage();

        $result = $this->get($this->pagePath($this->draftSlug()) . '?preview=1');

        $result->assertStatus(404);
    }

    public function testDraftPageIsNotFoundWithTamperedSignature(): void
    {
        $this->insertDraftPage();
        $token = $this->signPreview($this->previewIdentifier());

        $result = $this->get($this->previewPath($token, 'deadbeef'));

        $result->assertStatus(404);
    }

    public function testDraftPageIsNotFoundWithExpiredSignature(): void
    {
        $this->insertDraftPage();
        $token = $this->signPreview($this->previewIdentifier(), -60);

        $result = $this->get($this->previewPath($token));

        $result->assertStatus(404);
    }

    public function testDraftPageIsNotFoundWithSignatureForADifferentSlug(): void
    {
        $this->insertDraftPage();
        $token = $this->signPreview($this->languages[0]['code'] . ':' . $this->fixtures->slug('other-page'));

        $result = $this->get($this->previewPath($token));

        $result->assertStatus(404);
    }

    public function testDraftPageIsVisibleWithAValidSignature(): void
    {
        $this->insertDraftPage();
        $token = $this->signPreview($this->previewIdentifier());

        $result = $this->get($this->previewPath($token));
        $result->assertStatus(200);

        $body = json_decode($result->getJSON(), true);
        $this->assertSame($this->draftPageTitle, $body['data']['title']);
    }

    /** @return array<string, mixed> */
    private function pageTranslation(int $languagePosition): array
    {
        $language = $this->languages[$languagePosition];

        return [
            'language_id' => $language['id'],
            'slug' => $this->fixtures->slug('page', $language['code']),
            'title' => $this->fixtures->text('page-title', $language['code']),
            'excerpt' => $this->fixtures->text('page-excerpt', $language['code']),
            'og_image_url' => 'https://example.com/' . $this->fixtures->slug('image') . '.jpg',
        ];
    }

    private function insertDraftPage(): int
    {
        $translation = [
            'language_id' => $this->languages[0]['id'],
            'slug' => $this->draftSlug(),
            'title' => $this->draftPageTitle,
            'excerpt' => $this->fixtures->text('draft-excerpt', $this->languages[0]['code']),
        ];
        $page = $this->fixtures->page([$translation], [
            'status' => 'draft',
            'is_in_sitemap' => 0,
        ]);

        return $page['id'];
    }

    private function draftSlug(): string
    {
        return $this->draftPageSlug;
    }

    private function previewIdentifier(): string
    {
        return $this->languages[0]['code'] . ':' . $this->draftSlug();
    }

    /** @return array{expires:string,sig:string} */
    private function signPreview(string $identifier, int $ttlSeconds = 3600): array
    {
        $expires = (string) (time() + $ttlSeconds);

        return [
            'expires' => $expires,
            'sig' => hash_hmac('sha256', 'page:' . $identifier . ':' . $expires, (string) env('CMS_PREVIEW_SECRET', '')),
        ];
    }

    /** @param array{expires:string,sig:string} $token */
    private function previewPath(array $token, ?string $signature = null): string
    {
        return $this->pagePath($this->draftSlug()) . '?preview=1&preview_expires=' . $token['expires']
            . '&preview_sig=' . ($signature ?? $token['sig']);
    }

    private function pagePath(string $slug): string
    {
        return '/api/v1/public/' . $this->languages[0]['code'] . '/pages/' . $slug;
    }
}
