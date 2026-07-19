<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Cms;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Regression coverage for `PublicLanguageController::index()` after wrapping
 * its body in `handleRequest()` (2026-07-19) — previously it hand-built the
 * JSON response and bypassed the DTO-first orchestration every other
 * controller uses.
 *
 * @internal
 */
final class PublicLanguageControllerTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = true;
    protected $refresh     = true;
    protected $namespace   = 'App';

    protected function setUp(): void
    {
        parent::setUp();

        $this->db->query("DELETE FROM `cms_languages`");

        $this->db->table('cms_languages')->insert([
            'code'        => 'es',
            'name'        => 'Spanish',
            'native_name' => 'Español',
            'is_default'  => 1,
            'is_active'   => 1,
            'sort_order'  => 0,
        ]);

        $this->db->table('cms_languages')->insert([
            'code'        => 'en',
            'name'        => 'English',
            'native_name' => 'English',
            'is_default'  => 0,
            'is_active'   => 1,
            'sort_order'  => 1,
        ]);

        // Inactive languages must not appear in the public listing.
        $this->db->table('cms_languages')->insert([
            'code'        => 'fr',
            'name'        => 'French',
            'native_name' => 'Français',
            'is_default'  => 0,
            'is_active'   => 0,
            'sort_order'  => 2,
        ]);
    }

    public function testListsOnlyActiveLanguagesOrderedBySortOrder(): void
    {
        $result = $this->get('api/v1/cms/public/languages');

        $result->assertStatus(200);
        $result->assertJSONFragment(['status' => 'success']);

        $body = json_decode((string) $result->response()->getBody(), true);

        $this->assertSame(['es', 'en'], array_column($body['data'], 'code'));
        $this->assertTrue($body['data'][0]['is_default']);
        $this->assertFalse($body['data'][1]['is_default']);
    }
}
