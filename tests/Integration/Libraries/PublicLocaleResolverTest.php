<?php

declare(strict_types=1);

namespace Tests\Integration\Libraries;

use App\Libraries\Cms\PublicLocaleResolver;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Config\Database;
use Tests\Support\Fixtures\CmsFixtureFactory;

/**
 * @internal
 */
final class PublicLocaleResolverTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = true;
    protected $refresh     = true;
    protected $namespace   = 'App';

    private PublicLocaleResolver $resolver;

    /** @var list<array{id:int,code:string,name:string,is_default:bool}> */
    private array $languages;

    protected function setUp(): void
    {
        parent::setUp();

        $db = Database::connect();
        $db->query('DELETE FROM `cms_languages`');

        // languages(2) seeds two active languages; index 0 is is_default.
        $this->languages = (new CmsFixtureFactory($db, self::class))->languages(2);

        $this->resolver = new PublicLocaleResolver($db);
    }

    public function testResolvesActiveRequestedLocale(): void
    {
        // Note: only the first comma-separated segment is inspected, and it is
        // not stripped of a region subtag (e.g. "en-US" stays "en-us" and will
        // NOT match a stored 2-letter 'en' code) — this mirrors the exact
        // pre-existing behavior extracted from PublicMenuController::publicLocale(),
        // not a design goal of this resolver. Use a bare code here.
        $secondCode = $this->languages[1]['code'];

        $this->assertSame($secondCode, $this->resolver->resolve("{$secondCode};q=0.9"));
    }

    public function testFallsBackToDefaultWhenRequestedLocaleIsNotActive(): void
    {
        $defaultCode = $this->languages[0]['code'];

        $this->assertSame($defaultCode, $this->resolver->resolve('zz-ZZ,zz;q=0.9'));
    }

    public function testFallsBackToDefaultWhenHeaderIsMissing(): void
    {
        $defaultCode = $this->languages[0]['code'];

        $this->assertSame($defaultCode, $this->resolver->resolve(null));
    }

    public function testFallsBackToDefaultWhenHeaderIsEmptyString(): void
    {
        $defaultCode = $this->languages[0]['code'];

        $this->assertSame($defaultCode, $this->resolver->resolve(''));
    }

    public function testFallsBackToFrameworkLocaleWhenNoActiveDefaultLanguageExists(): void
    {
        $db = Database::connect();
        $db->query('DELETE FROM `cms_languages`');

        $this->assertSame((string) config('App')->defaultLocale, $this->resolver->resolve('zz-ZZ'));
    }
}
