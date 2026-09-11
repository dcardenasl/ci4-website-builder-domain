# ADR-014 — External domain binding for CMS page types

- **Status:** Accepted
- **Date:** 2026-08-25
- **Scope:** `ci4-website-builder-domain` and public consumers of the CMS

## Context

A site may need to deliver a CMS page whose content belongs to a different domain
application — for example, a detail route whose data is owned by a separate domain app. The
CMS still owns the template and the page configuration, but it must not infer the URL from
business names or couple itself to a specific application.

## Decision

The `page_type` field identifies the rendering contract, and an explicit binding
configuration associates that type with an external domain. The binding carries only
integration configuration: `page_type`, `domain_key`, `base_url`, and, when applicable, the
resolution path or contract. `domain_key` is a stable configuration identifier, not a URL
received directly from a public request.

Consumers resolve the binding through a per-environment configuration allow-list. An
arbitrary URL from a query string, CMS payload, or translatable content is never accepted.
The allow-list must validate scheme and host before building links, and must preserve the
local fallback when no binding exists for a given `page_type`.

## Consequences

- CMS templates stay reusable across sites and external domains.
- Deployment configuration is the source of truth for allowed hosts.
- A new binding requires configuration and consumer-side tests; it never requires creating a
  new domain app or copying business readers.
- Cache invalidation must cover both the CMS page and the external domain whenever the
  binding is active.

## Out of scope

- No table or migration is added for events, catalog, museum, or any other specific
  application.
- Dynamic domains are never resolved from editorial data without allow-list validation.
- The authentication contract is unchanged: the Hub remains the owner of IAM and tokens.
