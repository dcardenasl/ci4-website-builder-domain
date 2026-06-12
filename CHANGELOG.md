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
