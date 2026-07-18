# Final E2E and uncommitted-work audit

## Outcome

The local Domain, Admin, API, and Web scope was audited, corrected, and tested from an empty Domain database. No commit was created.

- Domain: 154 uncommitted paths.
- Admin: 17 uncommitted paths.
- API: 0 uncommitted paths.
- Web: 36 uncommitted paths.
- Root, outside the four Git repositories: `START_SERVERS.sh` was fixed to resolve PHP reliably, verify processes, and support `--foreground`.

## What the work represents

- **Domain:** final schema consolidated into creation migrations, repair migrations/commands/seeders and legacy code removed, one canonical media-reference contract, complete ES/EN demo-site seed, stricter CMS services, and architecture/integration ratchets.
- **Admin:** editor and previews aligned with the canonical media contract, real form relations, settings filters driven by seeded groups, and aligned tests/compiled asset.
- **API:** no local changes; validated as a clean consumer.
- **Web:** renderers and ViewModels consume only the canonical contract without legacy aliases/fallbacks; public resolution, cache, and headers were corrected; tests and compiled asset are aligned.

## Exact final inventory

### ci4-website-builder-domain

```text
 M CHANGELOG.md
 M TASKS.md
 D app/Commands/BackfillCmsFileReferences.php
 M app/Commands/PrepareTestDatabase.php
 D app/Commands/ReportLegacyBlockTextUsage.php
 M app/Config/CmsDomainServices.php
 M app/Controllers/Api/V1/Cms/FileUsageController.php
 M app/Controllers/Api/V1/Cms/FormController.php
 M app/DTO/Response/Cms/FormResponseDTO.php
 M app/Database/Migrations/2026-01-29-200038_CreateJobsTable.php
 M app/Database/Migrations/2026-01-29-200102_CreateFailedJobsTable.php
 M app/Database/Migrations/2026-01-29-201621_CreateRequestLogsTable.php
 M app/Database/Migrations/2026-01-29-201644_CreateMetricsTable.php
 M app/Database/Migrations/2026-01-29-205241_CreateAuditLogsTable.php
 D app/Database/Migrations/2026-03-04-000001_AddControlColumnsToAuditLogsTable.php
 M app/Database/Migrations/2026-05-06-100000_CreateIdempotencyKeysTable.php
 M app/Database/Migrations/2026-06-11-070001_CreateCmsSettings.php
 D app/Database/Migrations/2026-06-11-070002_CreateCmsPages.php
 D app/Database/Migrations/2026-06-11-070003_CreateCmsCollections.php
 M app/Database/Migrations/2026-06-11-070006_CreateCmsEntries.php
 M app/Database/Migrations/2026-06-11-070007_CreateCmsEntryRelations.php
 M app/Database/Migrations/2026-06-11-070008_CreateCmsBlocks.php
 M app/Database/Migrations/2026-06-11-070009_CreateCmsMenus.php
 D app/Database/Migrations/2026-06-11-070012_CreateCmsFormSubmissions.php
 M app/Database/Migrations/2026-06-11-070013_CreateCmsSearchIndex.php
 D app/Database/Migrations/2026-06-16-100001_AddPublicAndActiveToSettingsTable.php
 D app/Database/Migrations/2026-06-18-080000_BackfillHeroCarouselLayoutVariants.php
 D app/Database/Migrations/2026-06-18-090000_AddNewCmsBlockTypes.php
 D app/Database/Migrations/2026-06-22-100000_AddMetaToCmsSettings.php
 D app/Database/Migrations/2026-06-23-120000_AddSlugToCmsCollectionTranslations.php
 D app/Database/Migrations/2026-06-23-130000_DropUrlPrefixFromCmsCollections.php
 D app/Database/Migrations/2026-06-23-140000_AddPageTypesAboutHistoryEvents.php
 D app/Database/Migrations/2026-06-23-160000_NormalizeCmsSettingCanonicalValues.php
 M app/Database/Migrations/2026-06-24-100003_CreateCmsFormFields.php
 M app/Database/Migrations/2026-06-24-100004_CreateCmsFormFieldTranslations.php
 D app/Database/Migrations/2026-06-24-100005_AlterCmsFormSubmissionsAddFormId.php
 D app/Database/Migrations/2026-06-24-100006_SeedRecaptchaSettings.php
 D app/Database/Migrations/2026-06-25-100001_AddBlockTemplateToCmsCollections.php
 D app/Database/Migrations/2026-06-25-100002_NormalizeCmsCollectionBlockTemplate.php
 D app/Database/Migrations/2026-06-26-100001_AddWizardFieldsToCms.php
 D app/Database/Migrations/2026-06-26-100002_CleanupSeederInjectedWizardData.php
 D app/Database/Migrations/2026-06-27-100001_EnrichCmsSettingsWithInputType.php
 D app/Database/Migrations/2026-06-27-100002_AddUiMetaToCmsSettingTranslations.php
 D app/Database/Migrations/2026-06-27-180000_AddFeaturedImageUrlToCmsEntryTranslations.php
 D app/Database/Migrations/2026-06-28-100001_SetInputTypeForIdentitySettings.php
 D app/Database/Migrations/2026-06-30-100001_AddFulltextIndexToCmsContentBlocks.php
 D app/Database/Migrations/2026-07-01-100001_AddCollectionTypeToCmsCollections.php
 D app/Database/Migrations/2026-07-03-000001_BackfillLegacyBlockContentKeys.php
 D app/Database/Migrations/2026-07-03-090000_AddPortfolioPageTypeToCmsPages.php
 D app/Database/Migrations/2026-07-05-120001_MakeCollectionTypeDynamic.php
 D app/Database/Migrations/2026-07-06-100001_NormalizeCmsGenericBlockKeys.php
 D app/Database/Migrations/2026-07-06-110001_ExtractMapEmbedAndNormalizeGenericBlocks.php
 D app/Database/Migrations/2026-07-08-000001_AddCollectionIndexPageRelationToCmsPages.php
 D app/Database/Migrations/2026-07-09-000001_AddComponentsAndMediaPageTypesToCmsPages.php
 D app/Database/Migrations/2026-07-09-000001_BackfillPortfolioFeaturedImageUrls.php
 D app/Database/Migrations/2026-07-09-000002_FixPortfolioPageTypeEnumRegression.php
 D app/Database/Migrations/2026-07-10-000001_ExpandCmsFormFieldTypesAndOptions.php
 D app/Database/Migrations/2026-07-10-000002_MoveFormFieldOptionLabelsToTranslations.php
 D app/Database/Migrations/2026-07-14-120000_AddCollectionListingPresentationConfig.php
 D app/Database/Migrations/2026-07-15-090000_MigrateImageBlockToMediaReference.php
 D app/Database/Migrations/2026-07-16-090000_MigrateMediaFieldsToMediaReference.php
 D app/Database/Migrations/2026-07-16-110000_MigrateNestedImageFieldsToMediaReference.php
 D app/Database/Migrations/2026-07-16-120000_BackfillCmsMediaReferencePayloads.php
 D app/Database/Migrations/2026-07-16-130000_MigrateDocumentGalleryFileToMediaReference.php
 D app/Database/Migrations/2026-07-17-000001_AddOgImageUrlToCmsPageTranslations.php
 D app/Database/Migrations/Concerns/LegacyMediaReferenceResolver.php
 D app/Database/Seeds/BackfillImageBlockConventionSeeder.php
 D app/Database/Seeds/CleanupSpanishLegalPages.php
 M app/Database/Seeds/CmsBlockTypeSeeder.php
 M app/Database/Seeds/CmsHeroSliderChildrenSeeder.php
 M app/Database/Seeds/CmsPageBlockSeeder.php
 M app/Database/Seeds/Concerns/IdempotentSeederSupport.php
 D app/Database/Seeds/FixCollectionIndexPages.php
 M app/Database/Seeds/NewsCollectionSeeder.php
 M app/Database/Seeds/PortfolioCollectionSeeder.php
 M app/Database/Seeds/SiteAboutPageSeeder.php
 M app/Database/Seeds/SiteBootstrapSeeder.php
 M app/Database/Seeds/SiteComponentsPageSeeder.php
 M app/Database/Seeds/SiteHistoryPageSeeder.php
 D app/Database/Seeds/SiteLegalPagesSeeder.php
 M app/Database/Seeds/SiteLegalPagesSeederChile.php
 M app/Database/Seeds/SiteMediaPageSeeder.php
 M app/Database/Seeds/SitePortfolioPageSeeder.php
 M app/Database/Seeds/SiteSocialLinksSeeder.php
 M app/Language/en/Cms.php
 M app/Language/en/Forms.php
 M app/Language/es/Cms.php
 M app/Language/es/Forms.php
 M app/Libraries/Cms/BlockInstanceSerializer.php
 M app/Libraries/Cms/BlockSchemaIntrospector.php
 M app/Libraries/Cms/BlockTemplateCatalog.php
 D app/Libraries/Cms/BlockTextPayload.php
 M app/Libraries/Cms/CmsEnums.php
 M app/Libraries/Cms/EntryListingContentResolver.php
 M app/Libraries/Cms/FieldPrimitiveRegistry.php
 M app/Libraries/Cms/FileReferenceSynchronizer.php
 M app/Libraries/Cms/FileUrlResolver.php
 M app/Libraries/Cms/TranslationResolver.php
 M app/Libraries/Cms/TranslationResourceCatalog.php
 M app/Services/Cms/BlockInstanceService.php
 M app/Services/Cms/BlockInstanceTranslationAuditor.php
 M app/Services/Cms/BlockTypeService.php
 M app/Services/Cms/CategoryService.php
 M app/Services/Cms/CollectionService.php
 M app/Services/Cms/EntryBlockTemplateInitializer.php
 M app/Services/Cms/EntryService.php
 M app/Services/Cms/FileUsageService.php
 M app/Services/Cms/FormService.php
 M app/Services/Cms/FormSubmissionService.php
 M app/Services/Cms/MenuItemService.php
 M app/Services/Cms/MenuService.php
 M app/Services/Cms/PageService.php
 M app/Services/Cms/PublicEntryReader.php
 M app/Services/Cms/RedirectService.php
 M app/Services/Cms/SettingService.php
 M app/Services/Cms/TagService.php
 M docs/architecture/FILES.es.md
 M docs/architecture/FILES.md
 M tests/Feature/Controllers/Cms/PublicEntryControllerTest.php
 D tests/Integration/Commands/BackfillCmsFileReferencesTest.php
 M tests/Integration/Database/Seeds/NewsCollectionSeederTest.php
 M tests/Integration/Database/Seeds/SiteAboutPageSeederTest.php
 M tests/Integration/Database/Seeds/SiteBootstrapContentTest.php
 M tests/Integration/Database/Seeds/SiteBootstrapPublicBlockCoverageTest.php
 M tests/Integration/Database/Seeds/SiteBootstrapSeederTest.php
 M tests/Integration/Database/Seeds/SiteComponentsPageSeederTest.php
 M tests/Integration/Database/Seeds/SiteMediaPageSeederTest.php
 M tests/Integration/Database/Seeds/SiteMenuSeederTest.php
 M tests/Integration/Database/Seeds/SitePortfolioPageSeederTest.php
 M tests/Integration/Libraries/BlockInstanceSerializerTest.php
 M tests/Integration/Libraries/TranslationResolverTest.php
 D tests/Integration/Migrations/NormalizeCmsSettingCanonicalValuesTest.php
 M tests/Unit/Architecture/ServiceModelDependencyConventionsTest.php
 M tests/Unit/Libraries/Cms/BlockSchemaIntrospectorTest.php
 D tests/Unit/Libraries/Cms/BlockTextPayloadTest.php
 M tests/Unit/Libraries/Cms/EntryListingContentResolverTest.php
 M tests/Unit/Libraries/Cms/FileUrlResolverTest.php
 M tests/Unit/Services/Cms/BlockInstanceServiceTest.php
 M tests/Unit/Services/Cms/EntryServiceTest.php
 M tests/Unit/Services/Cms/MenuItemServiceTest.php
 M tests/Unit/Services/Cms/PageServiceTest.php
 M tests/Unit/Services/Cms/SettingServiceTest.php
 M tests/Unit/Services/Cms/TagServiceTest.php
?? app/Database/Migrations/2026-06-11-070002_CreateCmsCollections.php
?? app/Database/Migrations/2026-06-11-070003_CreateCmsPages.php
?? app/Database/Migrations/2026-06-24-100005_CreateCmsFormSubmissions.php
?? app/Database/Seeds/SiteIntegrationSettingsSeeder.php
?? tests/Integration/Database/Seeds/SiteBootstrapSchemaAlignmentTest.php
?? tests/Integration/Libraries/FileReferenceSynchronizerTest.php
?? tests/Unit/Architecture/CleanDatabaseBootstrapConventionsTest.php
?? docs/audits/2026-07-18-browser-e2e-uncommitted-validation.es.md
?? docs/audits/2026-07-18-browser-e2e-uncommitted-validation.md
?? docs/audits/2026-07-18-migrations-seeders-clean-baseline.es.md
?? docs/audits/2026-07-18-migrations-seeders-clean-baseline.md
```

### ci4-website-builder-admin

```text
 M app/Common.php
 M app/Helpers/form_helper.php
 M app/Modules/Cms/Controllers/FormController.php
 M app/Modules/Cms/Language/en/Settings.php
 M app/Modules/Cms/Language/es/Settings.php
 M app/Views/cms/block_types/previews/document_gallery.php
 M app/Views/cms/block_types/previews/video_gallery.php
 M app/Views/cms/forms/show.php
 M app/Views/cms/pages/blocks/create.php
 M app/Views/cms/pages/blocks/edit.php
 M app/Views/cms/settings/partials/filters.php
 M app/Views/components/form/media_reference.php
 M public/assets/css/app.css
 M src/js/utils/fileUrl.test.js
 M tests/unit/Helpers/FormHelperTest.php
 M tests/unit/Views/Cms/BlockEditViewTest.php
 M tests/unit/Views/Cms/SettingsFiltersViewTest.php
```

### ci4-website-builder-api

_Sin cambios locales._

### ci4-website-builder-web

```text
 D app/Commands/ReportLegacyBlockTextUsage.php
 M app/Common.php
 M app/Config/ContentSecurityPolicy.php
 M app/Controllers/BlockPreviewController.php
 M app/Filters/SecurityHeadersFilter.php
 M app/Libraries/WebApiClient.php
 M app/ViewModels/Blocks/AbstractBlockViewModel.php
 M app/ViewModels/Blocks/AssetShowcaseViewModel.php
 M app/ViewModels/Blocks/CardsSliderViewModel.php
 M app/ViewModels/Blocks/CollectionGridViewModel.php
 M app/ViewModels/Blocks/CollectionListingViewModel.php
 M app/ViewModels/Blocks/DocumentDownloadViewModel.php
 M app/ViewModels/Blocks/HeroBannerViewModel.php
 M app/ViewModels/Blocks/HeroSliderViewModel.php
 M app/ViewModels/Blocks/PdfViewerViewModel.php
 M app/ViewModels/Blocks/TeamMemberViewModel.php
 M app/ViewModels/Blocks/VideoPlayerViewModel.php
 M app/Views/blocks/alert.php
 M app/Views/blocks/gallery_item.php
 M app/Views/blocks/image.php
 M public/assets/css/compiled.css
 M tests/feature/CacheControllerTest.php
 M tests/feature/PageResolutionTest.php
 D tests/unit/LegacyBlockTextHelpersTest.php
 M tests/unit/Libraries/BlockRendererTest.php
 M tests/unit/Libraries/CacheInvalidatorTest.php
 M tests/unit/ViewModels/Blocks/CardsSliderViewModelTest.php
 M tests/unit/ViewModels/Blocks/DocumentDownloadViewModelTest.php
 M tests/unit/ViewModels/Blocks/DocumentGalleryViewModelTest.php
 M tests/unit/ViewModels/Blocks/HeroBannerViewModelTest.php
 M tests/unit/ViewModels/Blocks/HeroSliderViewModelTest.php
 M tests/unit/ViewModels/Blocks/PdfViewerViewModelTest.php
 M tests/unit/ViewModels/Blocks/TeamMemberViewModelTest.php
 M tests/unit/ViewModels/Blocks/VideoGalleryViewModelTest.php
 M tests/unit/ViewModels/Blocks/VideoPlayerViewModelTest.php
 M tests/unit/Views/Blocks/HeroSliderViewTest.php
```

## Executed E2E plan

1. Rebuild the Domain database from scratch and run all 26 migrations.
2. Run `SiteBootstrapSeeder` and verify the complete 26-seeder graph.
3. Start all four services using the root `START_SERVERS.sh`.
4. Authenticate to Admin with the root README credentials.
5. Validate dashboard and Hub, Domain, Web, database, and writable health.
6. Validate pages, blocks, child carousel, slide editing, and preview.
7. Validate collections, entries, forms, usage relations, and delete protection.
8. Validate settings and the actual identity, contact, integration, analytics, and social groups.
9. Navigate home, about, history, portfolio, news, media, contact, component catalog, and landing pages in Spanish.
10. Exercise carousel, modal gallery, search, filters, and FAQ.
11. Validate English home and legal notice.
12. Verify ES/EN legal pages contain no template tokens and inspect browser consoles.

## Evidence and corrected findings

- The main database was dropped and recreated; all 26 `Create*` migrations and the full bootstrap completed.
- Admin authenticated with `admin@example.com` / `ChangeMe123!` from the root README.
- Settings displayed only the five real groups and all eight seeded social URLs.
- Slide #18 retained the `{source_kind,file_id,url}` payload and expected external URL.
- Verified 19 ES/EN pages, 3 home slides, 2 collections, 5 published entries, and 2 forms.
- The contact form displayed three required fields and its relation to the Contact page.
- Home, editorial pages, search/filters, galleries, documents, videos, FAQ, and ES/EN localization worked.
- Legal notices rendered `Mi Sitio Demo SpA` and `My Demo Site LLC`; no `[TU_*]`, `[YOUR_*]`, or `[SOCIAL_*]` tokens remain.
- Final Admin and Web consoles: 0 errors, 0 warnings.

## Automated gates

- Domain: `composer quality` — 430 tests, 5,509 assertions, 1 skip; PHPStan, style, architecture, OpenAPI, and i18n passed.
- Admin: `composer ci` — 576 tests, 2,061 assertions, 1 skip; PHPStan, style, and i18n passed. JS: 59 tests, lint, and build passed.
- API: `composer quality` — 662 tests, 1,786 assertions, 2 skips; all gates passed.
- Web: `composer quality` — 179 tests, 526 assertions, 5 skips; PHPStan, style, and i18n passed. JS lint and build passed.
- Schema/seed alignment: 1 test, 3,704 assertions.
- The launcher passed `bash -n`, started all five processes, and stopped them cleanly after testing.

## Final state

There are no known pending failures in scope. Servers were stopped cleanly after the E2E session.
