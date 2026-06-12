# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
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



