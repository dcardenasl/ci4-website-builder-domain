<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use Config\Services;

/**
 * Seeds the full starter/demo site: content, forms, pages, block type examples,
 * collections, and menus. Migrations define the CMS structure; this seeder is not
 * required for the wizard to run.
 */
class SiteBootstrapSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(CmsLanguageSeeder::class);
        $this->call(CmsFormSeeder::class);
        $this->call(SiteIdentitySeeder::class);
        $this->call(SiteContactDefaultsSeeder::class);
        $this->call(SiteIntegrationSettingsSeeder::class);
        $this->call(AnalyticsSeeder::class);
        $this->call(SiteSocialLinksSeeder::class);
        $this->call(CmsBlockTypeSeeder::class);
        $this->call(SitePagesSeeder::class);
        $this->call(NewsCollectionSeeder::class);
        $this->call(SiteNewsPageSeeder::class);
        $this->call(WizardConfigSeeder::class);
        $this->call(CmsPageBlockSeeder::class);
        $this->call(CmsHeroSliderChildrenSeeder::class);
        $this->call(CmsSocialLinksChildrenSeeder::class);
        $this->call(SiteAboutPageSeeder::class);
        $this->call(SiteHistoryPageSeeder::class);
        $this->call(PortfolioCollectionSeeder::class);
        $this->call(SitePortfolioPageSeeder::class);
        $this->call(SiteComponentsPageSeeder::class);
        $this->call(SiteMediaPageSeeder::class);
        $this->call(SiteLandingPageSeeder::class);
        $this->call(SiteLegalPagesSeederChile::class);
        $this->call(SiteLegalMenuSeeder::class);
        $this->call(SiteMenuSeeder::class);

        // Seeders write directly for deterministic bootstrap performance, so
        // finish through the same canonical reference synchronizer used by the
        // application services. A fresh install is immediately consistent and
        // never depends on a repair command.
        Services::fileReferenceSynchronizer(false)->rebuildAll();
    }
}
