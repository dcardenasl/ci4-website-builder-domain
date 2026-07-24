<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Cms;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\Fixtures\CmsFixtureFactory;

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

    /** @var list<array{id:int,code:string,name:string,is_default:bool}> */
    private array $languages;

    protected function setUp(): void
    {
        parent::setUp();

        $this->db->query("DELETE FROM `cms_languages`");

        $this->languages = (new CmsFixtureFactory($this->db, self::class))->languages(3);
        $this->db->table('cms_languages')
            ->where('id', $this->languages[2]['id'])
            ->update(['is_active' => 0]);
    }

    public function testListsOnlyActiveLanguagesOrderedBySortOrder(): void
    {
        $result = $this->get('api/v1/cms/public/languages');

        $result->assertStatus(200);
        $result->assertJSONFragment(['status' => 'success']);

        $body = json_decode((string) $result->response()->getBody(), true);

        $this->assertSame(
            array_column(array_slice($this->languages, 0, 2), 'code'),
            array_column($body['data'], 'code'),
        );
        $this->assertTrue($body['data'][0]['is_default']);
        $this->assertFalse($body['data'][1]['is_default']);
    }
}
