# File Architecture

This document defines the canonical file model for CMS content.

## Source of truth

- `file_id` identifies the file.
- `file_references` is the canonical "where used" registry.
- Persisted URLs are derived output, not canonical data.
- The backend must resolve the final URL for the current context.

## Read contract

- Public responses must return URLs, not admin preview routes.
- If a payload contains `file_id`, the backend resolves the URL from the `files` table.
- If legacy data still contains an admin preview URL, the backend may resolve the file ID from that URL and rewrite the output.
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
- Block serializers must normalize `*_url` fields from the canonical file ID.

## Reference sync rules

- Rebuild `file_references` after saving entries, pages, and block instances.
- Delete and reinsert references for the same resource to avoid stale rows.
- Keep references stable across file replacement. The file ID changes; the usage stays.

## Backfill and cleanup

- Run the backfill command when legacy rows contain admin URLs or missing references.
- The backfill must be idempotent.
- It must normalize URLs, infer file IDs when possible, and rebuild references.

## What not to do

- Do not persist `/files/{id}/view` as canonical CMS data.
- Do not derive file URLs in the frontend.
- Do not update file references outside the save transaction.
- Do not invent new storage rules per feature.

## Adding a new file field

1. Add a `file` field to the schema.
2. Persist the `*_file_id` as the identity.
3. Let the backend derive the final `*_url`.
4. Register or rebuild references for the new usage.
5. Add a regression test for save, read, and backfill.
