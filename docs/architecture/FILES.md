# File Architecture

This document defines the canonical file model for CMS content.

## Source of truth

- `media_reference` is the only block-schema primitive for media fields.
- Its persisted shape is `{source_kind, file_id, url}`.
- `file_id` identifies a Hub-owned file; external URLs have a null `file_id`.
- `cms_file_references` is the Domain-owned canonical "where used" registry.
- The Domain database never owns a `files` table and never adds a foreign key to Hub data.
- Persisted URLs are derived output, not canonical data.
- The backend must resolve the final URL for the current context.

## Read contract

- Public responses must return URLs, not admin preview routes.
- If a payload contains `file_id`, the backend resolves the URL through the Hub.
- The frontend must never invent file paths.

## Write contract

- Every CMS write path that associates a file must register references in the same transaction.
- The canonical resource types are:
  - `entry`
  - `page`
  - `block_instance`
- The canonical role must describe the semantic use and language or field path.
- Keep the admin label human-readable.

## URL resolution

- The resolver prefers image variants when they exist.
- Public consumption should use the backend-resolved URL, not the raw admin preview URL.
- Block serializers batch-resolve media-reference URLs from the canonical file ID.

## Reference sync rules

- Rebuild `cms_file_references` inside the same transaction after saving entries, pages, block instances, or media-bearing block schemas.
- Delete and reinsert references for the same resource to avoid stale rows.
- Keep references stable across file replacement. The file ID changes; the usage stays.

## Day-zero consistency

- The base migration creates `cms_file_references` with the rest of the block structure.
- Seeders write canonical media payloads exclusively.
- `SiteBootstrapSeeder` rebuilds the registry after direct seeder writes.
- No migrations or commands convert previous payload formats.

## What not to do

- Do not persist `/files/{id}/view` as canonical CMS data.
- Do not derive file URLs in the frontend.
- Do not update file references outside the save transaction.
- Do not invent new storage rules per feature.

## Adding a new file field

1. Add a `media_reference` field to the schema and declare its `accept` value.
2. Persist the nested `{source_kind, file_id, url}` value.
3. Let the backend resolve the final URL in batch.
4. Register or rebuild `cms_file_references` for the new usage.
5. Add a regression test for save, read, and reference synchronization.
