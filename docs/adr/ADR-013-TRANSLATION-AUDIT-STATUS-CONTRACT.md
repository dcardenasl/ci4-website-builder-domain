# ADR-013: Translation Audit Status Contract

## Status
Accepted

## Context

The CMS serves multilingual public content. The translation audit needs to distinguish between truly missing translations and partial or inconsistent content so editors can fix the right issue first.

## Decision

1. The audit reports three statuses:
- `missing`: the translation row does not exist for the active language.
- `incomplete`: the translation row exists, but one or more required public fields are blank.
- `mismatch`: the translation row exists, all required fields are present, but an optional public field is filled in at least one language and blank in another.
2. Required fields are the only fields that can trigger `incomplete` on their own.
3. Optional fields do not trigger a warning when they are blank in every language.
4. Optional fields do trigger `mismatch` when there is asymmetry between languages.
5. Structural or internal fields are excluded unless the schema marks them as auditable/public.
6. The same contract applies to pages, menus, menu items, collections, categories, tags, entries, forms, form fields, settings, and block instances.

## Consequences

### Positive
- Editors see the real problem instead of a generic missing-translation warning.
- The admin audit becomes useful for both initial content creation and later drift detection.
- Block audits stay schema-driven and avoid false positives on internal configuration fields.

### Trade-offs
- The audit is stricter than before and may surface more findings in existing content.
- Block schemas must keep required/public field metadata accurate for the audit to stay trustworthy.
