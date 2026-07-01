<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Seeds the full starter site: content, forms, pages, blocks, collections, and menus.
 */
class SiteBootstrapSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(CmsLanguageSeeder::class);
        $this->call(CmsFormSeeder::class);
        $this->call(SiteIdentitySeeder::class);
        $this->call(SiteContactDefaultsSeeder::class);
        $this->call(CmsBlockTypeSeeder::class);
        $this->call(SitePagesSeeder::class);
        $this->call(NewsCollectionSeeder::class);
        $this->call(CmsPageBlockSeeder::class);
        $this->call(CmsHeroSliderChildrenSeeder::class);
        $this->call(SiteAboutPageSeeder::class);
        $this->call(SiteHistoryPageSeeder::class);
        $this->call(SiteEventsPageSeeder::class);
        $this->call(SiteMenuSeeder::class);
    }
}
