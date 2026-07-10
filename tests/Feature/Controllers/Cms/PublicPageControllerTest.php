<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Cms;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * @internal
 */
final class PublicPageControllerTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = true;
    protected $refresh     = true;
    protected $namespace   = 'App';

    private int $langEsId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->db->disableForeignKeyChecks();
        $this->db->query("DELETE FROM `cms_page_translations`");
        $this->db->query("DELETE FROM `cms_pages`");
        $this->db->query("DELETE FROM `cms_languages`");
        $this->db->enableForeignKeyChecks();

        // Seed language
        $this->db->table('cms_languages')->insert([
            'code'       => 'es',
            'name'       => 'Spanish',
            'is_default' => 1,
            'is_active'  => 1,
        ]);
        $this->langEsId = $this->db->insertID();
    }

    public function testGetPublicPageSuccess(): void
    {
        $this->db->table('cms_pages')->insert([
            'status' => 'published',
            'page_type' => 'generic',
            'sort_order' => 1,
            'is_in_sitemap' => 1,
        ]);
        $pageId = $this->db->insertID();

        $this->db->table('cms_page_translations')->insert([
            'page_id'     => $pageId,
            'language_id' => $this->langEsId,
            'slug'        => 'nosotros',
            'title'       => 'Sobre Nosotros',
            'excerpt'     => 'Esta es la pagina sobre nosotros.',
        ]);

        $result = $this->get('/api/v1/public/es/pages/nosotros');

        $result->assertStatus(200);

        $body = json_decode($result->getJSON(), true);
        $this->assertSame('success', $body['status']);
        $this->assertSame('nosotros', $body['data']['slug']);
        $this->assertSame('Sobre Nosotros', $body['data']['title']);
        $this->assertSame('Esta es la pagina sobre nosotros.', $body['data']['excerpt']);
    }

    public function testGetPublicPageNotFound(): void
    {
        $result = $this->get('/api/v1/public/es/pages/no-existe');
        $result->assertStatus(404);
    }

    private function insertDraftPage(): int
    {
        $this->db->table('cms_pages')->insert([
            'status'        => 'draft',
            'page_type'     => 'generic',
            'sort_order'    => 1,
            'is_in_sitemap' => 0,
        ]);
        $pageId = $this->db->insertID();

        $this->db->table('cms_page_translations')->insert([
            'page_id'     => $pageId,
            'language_id' => $this->langEsId,
            'slug'        => 'borrador',
            'title'       => 'Página en borrador',
            'excerpt'     => 'No debe ser visible sin una firma válida.',
        ]);

        return $pageId;
    }

    private function signPreview(string $type, string $identifier, int $ttlSeconds = 3600): array
    {
        $expires = (string) (time() + $ttlSeconds);

        return [
            'expires' => $expires,
            'sig'     => hash_hmac('sha256', $type . ':' . $identifier . ':' . $expires, (string) env('CMS_PREVIEW_SECRET', '')),
        ];
    }

    public function testDraftPageIsNotFoundByDefault(): void
    {
        $this->insertDraftPage();

        $result = $this->get('/api/v1/public/es/pages/borrador');
        $result->assertStatus(404);
    }

    public function testDraftPageIsNotFoundWithBarePreviewFlagAndNoSignature(): void
    {
        $this->insertDraftPage();

        $result = $this->get('/api/v1/public/es/pages/borrador?preview=1');
        $result->assertStatus(404);
    }

    public function testDraftPageIsNotFoundWithTamperedSignature(): void
    {
        $this->insertDraftPage();
        $token = $this->signPreview('page', 'es:borrador');

        $result = $this->get('/api/v1/public/es/pages/borrador?preview=1&preview_expires=' . $token['expires'] . '&preview_sig=deadbeef');
        $result->assertStatus(404);
    }

    public function testDraftPageIsNotFoundWithExpiredSignature(): void
    {
        $this->insertDraftPage();
        $token = $this->signPreview('page', 'es:borrador', -60);

        $result = $this->get('/api/v1/public/es/pages/borrador?preview=1&preview_expires=' . $token['expires'] . '&preview_sig=' . $token['sig']);
        $result->assertStatus(404);
    }

    public function testDraftPageIsNotFoundWithSignatureForADifferentSlug(): void
    {
        $this->insertDraftPage();
        $token = $this->signPreview('page', 'es:otra-pagina');

        $result = $this->get('/api/v1/public/es/pages/borrador?preview=1&preview_expires=' . $token['expires'] . '&preview_sig=' . $token['sig']);
        $result->assertStatus(404);
    }

    public function testDraftPageIsVisibleWithAValidSignature(): void
    {
        $this->insertDraftPage();
        $token = $this->signPreview('page', 'es:borrador');

        $result = $this->get('/api/v1/public/es/pages/borrador?preview=1&preview_expires=' . $token['expires'] . '&preview_sig=' . $token['sig']);
        $result->assertStatus(200);

        $body = json_decode($result->getJSON(), true);
        $this->assertSame('Página en borrador', $body['data']['title']);
    }
}
