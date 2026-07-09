# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- **Component and media page types** — migration `2026-07-09-000001_AddComponentsAndMediaPageTypesToCmsPages` introduces two new page types (`component` and `media`) for modular page composition; adds `SiteComponentsPageSeeder` and `SiteMediaPageSeeder` to bootstrap example pages with full block coverage validation tests
- **Map embed block extraction** — migration `2026-07-06-110001_ExtractMapEmbedAndNormalizeGenericBlocks` separates embedded maps from `contact_info` block into independent `map_embed` block for better modularity; automatically migrates existing `contact_info` instances that include map URLs to the new dedicated block type

### Changed
- **Generic block naming normalization** — consolidated domain-specific block types (news_grid, portfolio_grid, events_grid, contact_form, location_info, faq_accordion, etc.) into unified generic block names (collection_grid, form_embed, contact_info, accordion, etc.); migration `NormalizeCmsGenericBlockKeys` automatically migrates existing instances; improves consistency and reduces block type proliferation

### Added
- **Dynamic ordering for entry listings** — enhanced `PublicEntryIndexRequestDTO` and `PublicEntryReader` with `order_by` (published_at, created_at, sort_order, title) and `order_direction` (asc/desc) parameters; public `/api/v1/cms/public/{lang}/entries/{collection}` endpoint now supports flexible sorting with title-based ordering via join to translations table
- **Unified collection grid block** — consolidated a set of collection-specific grid block types into a single `collection_grid` block with flexible configuration (order_by, order_direction, layout_variant); updated `BlockTemplateCatalog` and database seeders to use the new unified block
- **Dynamic collection types system** — migrated `collection_type` from ENUM to VARCHAR for flexibility; added `BlockSchemaIntrospector` and `FieldPrimitiveRegistry` libraries for dynamic schema analysis and field primitive normalization; enables collections to have custom types beyond fixed presets
- **Collection-aware entry creation and validation** — enhanced `CollectionCreateRequestDTO`, `CollectionUpdateRequestDTO`, and `EntryCreateRequestDTO` with collection type awareness; `EntryService` now validates and manages entries within their collection context
- **Wizard configuration API** — new `WizardConfigController` with endpoints for retrieving wizard metadata; enhanced seeders (`CmsBlockTypeSeeder`, `WizardConfigSeeder`) to bootstrap wizard configuration and block type templates
- **Entry list filtering by collection** — added `collection_id` filter parameter to `EntryIndexRequestDTO` and model filterable fields; enables admin panel to filter entries by collection in the reorder view
- **Setting connections management** — new `SettingConnectionController` with CRUD endpoints for managing setting connections; enhanced `SettingEntity` and `SettingTranslationEntity` with `input_type` and `ui_meta` fields for richer settings configuration; added 4 migrations for schema enrichment
- **File usage tracking with Hub integration** — new `FileUsageController` and `FileUsageService` to track Hub file references across Domain CMS (entries, pages, blocks, settings); refactored `FileUrlResolver` to use `HubClient` for centralized file URL resolution with caching; added batch-file metadata resolution via `HubClient::resolvePublicFileMeta()` for efficient URL lookups
- **Forms API (CMS-012)** — complete form management system with `FormController` (CRUD), `PublicFormController` (public submission handling), form fields, multilingual translations, submission tracking with autoreply and notification jobs, and web app key authentication filter
- **Entry block CRUD endpoints** — `BlockInstanceController` now supports both page and entry blocks with dynamic permission routing; `ownerTypeFromRequest()` and `requiresPermission()` methods automatically resolve correct permission codes (`cms.entries.*` vs `cms.pages.*`) based on URI segments
- **Analytics API** — new `POST /api/v1/cms/analytics/track` public endpoint for recording page views, `GET /api/v1/cms/analytics/overview` for analytics overview with period filtering, `PageViewModel` model with multi-field tracking (URL, title, referrer, user agent, device type), and `AnalyticsService` for structured analytics queries
- **Collection slug support** — add `slug` field to `CollectionTranslationEntity` with migration, enabling SEO-friendly collection URLs alongside IDs
- **Form submissions API** — new public endpoint `POST /api/v1/public/submissions` and admin CRUD (`FormSubmissionController`) for managing form submissions with status tracking (new, read, replied, spam, archived)
- **Settings meta field** — new `setting_meta` JSON column for storing auxiliary metadata (file URLs, MIME types) with migration and DTO support
- **Translation audit module** — new `TranslationAuditController` with audit endpoints for multi-language translation coverage, completeness tracking, and language statistics across pages, menu items, and settings
- **Localized slugs in entry and page responses** — API responses now include `localized_slugs` map for all active languages, enabling multi-language URL construction on the frontend without additional API calls
- **Block composition hierarchy** — define `allowed_children` configuration for container block types to enforce structural constraints and prevent invalid nesting
- **Slug availability validation endpoints** — new `checkSlug()` methods in `PageController`, `EntryController`, and `CategoryController` to validate slug uniqueness within a language context
- **Public Settings endpoint** — `GET /api/v1/public/{lang}/settings` to expose public settings with `public` flag filtering

### Fixed
- **Category response enrichment** — `CategoryResponseDTO` and `CategoryService` now include `collection_name` and `parent_label` fields for admin UI display; service fetches and maps collection and parent category translations for cleaner relational labels
- **Legacy rich-text block keys** — `BlockInstanceSerializer` and `BlockInstanceService` normalize `body`/`html`/`text` payload keys to the canonical `content` field on read and write; a backfill migration (`BackfillLegacyBlockContentKeys`) normalizes existing rows
- **CMS DTO validation** — strengthen translation normalization in `MenuCreateRequestDTO`, `MenuUpdateRequestDTO`, `BlockTypeCreateRequestDTO` with trimming and filtering of empty values
- **CMS Service response mapping** — add `name` and `slug` fields to `CategoryService`, `EntryService`, and `TagService` responses by extracting from primary translations
- **CMS Response DTOs** — add serialization fields to `CategoryResponseDTO`, `EntryResponseDTO`, and `TagResponseDTO` for consistent API contracts
- **Hub configuration** — enforce required `hub.url`, `hub.apiKey`, and `hub.appCode` with clear error messages and i18n support
- **BlockType service** — validate `block_key` uniqueness with translated error messages
- **CMS Request DTOs** — Refined field validation rules and type hinting across all request DTOs
- **Settings model and service** — Added support for public flag and active status with database migration and language keys

### Added
- **Scheduled Publishing (CMS-011)** — developed `ScheduledPublishingJob` queue job and `cms:publish-scheduled` Spark CLI command to transition scheduled pages and entries to published status on schedule, with automatic version snapshot generation and transaction-backed idempotency
- **Redirects & Slug history (CMS-010)** — manual redirects CRUD and automatic slug history tracking with a deep module trigger `SlugRedirectRecorder`, resolved via `PublicRedirectController` with full path resolution
- **Pages API** — full CRUD with multi-language translations, draft/published versioning, and hierarchical slug routing for SEO-friendly URLs
- **SlugRouter library** — deep module for resolving pages by slug with automatic language fallback and canonical URL handling
- **Languages API** — full CRUD for managing multi-language configurations with validation and default-language enforcement
- **Settings API** — hierarchical settings system with per-language translations and translatability flag
- **TranslationResolver library** — deep module for resolving translated content with fallback chains (target → default → fallback-of-target)
- **FileTranslation API** — full CRUD for managing file translations with BlockInstanceSerializer for content block serialization
- **Menu API** — full CRUD with multi-language translations, hierarchical structure support, and public menu resolution endpoints
- **Block System API** — full CRUD for managing BlockTypes (seeded with rich_text, image, cta) and BlockInstances nested under pages, with a unified BlockInstanceSerializer for merging structural config and localized data, supporting translation fallbacks and image metadata enrichment
- **Collections API** — full CRUD under `/cms/collections` protected with permissions, multi-language translation integration resolved via `TranslationResolver` with fallbacks, and a public listing endpoint on `GET public/{lang}/collections` for active collections
- **Entries API** — full CRUD under `/cms/entries` protected with `cms.entries.*` permissions, version snapshot history, multi-language translation integration, and public endpoints on `GET public/{lang}/entries/{collection}` for paginated listings and `GET public/{lang}/entries/{collection}/{slug}` for detail views with serialized block instances
- **Taxonomies API (Categories & Tags)** — Category and Tag CRUD with multi-language translations, pivot tables linking entries to taxonomies, public entries filtering by category/tag slug, and resolved taxonomies inside public entry responses



