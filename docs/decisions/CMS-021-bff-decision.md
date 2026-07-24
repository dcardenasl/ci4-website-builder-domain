# CMS-021: Backend-for-Frontend (BFF) Architecture Decision

**Date:** 2026-06-27  
**Status:** ✅ **DECIDED — Direct Consumption**  
**Affected Components:** Domain CMS, Public Website, Admin  
**Milestone:** TeatroMuseo v1.0 (MVP)

---

## Question

Should `ci4-website-builder-web` (public website) consume the Domain CMS API (`ci4-website-builder-domain`) directly, or should there be an intermediate **Backend-for-Frontend (BFF)** layer (`ci4-bff-starter`) that aggregates Domain + Hub APIs and exposes a specialized web API?

---

## Context

- **ci4-website-builder-web** currently talks to Domain CMS API for pages, entries, menus, settings
- **ci4-bff-starter** exists as a template for multi-consumer aggregation patterns
- **TeatroMuseo** is the first production application using this CMS
- **Scope:** Single website consuming a single headless API (Domain CMS)
- **Future:** Potentially multiple frontends (mobile apps, second website) in v0.3+

---

## Decision: ✅ **DIRECT CONSUMPTION**

**For TeatroMuseo v1.0 MVP:** The public website (`ci4-website-builder-web`) **directly consumes** the Domain CMS API.  
The BFF pattern is deferred to **v0.3 or later** if/when multiple frontends need aggregated APIs.

---

## Rationale

### ✅ Direct Consumption is Optimal for MVP
| Aspect | Direct | BFF | Verdict |
|--------|--------|-----|---------|
| **Deployment complexity** | ✓ Simpler (2 apps) | ✗ More (3 apps) | Direct wins |
| **Request latency** | ✓ ~50ms | ✗ ~80-100ms | Direct wins |
| **Data transformation** | ✓ In web layer | ✗ In BFF layer | Tie (same complexity) |
| **Code reuse** | ✓ Web owns UI-specific logic | ✗ Duplicated in BFF | Direct wins |
| **Single-consumer scenario** | ✓ Optimized | ✗ Over-engineered | Direct wins |

### 🔄 When to Introduce BFF
Introduce a BFF layer only when **all three** conditions are true:
1. **Multiple frontends** need the same API (mobile app + web, or second website)
2. **API contract differences** are significant (mobile needs different response shapes)
3. **Aggregation logic** is complex enough to justify a separate layer (M2M auth, webhook proxying, rate limit management)

**Current state:** Only 1 frontend (web) → BFF adds unnecessary complexity.

---

## Implementation for TeatroMuseo

**Architecture:**
```
┌─────────────────┐
│  TeatroMuseo    │
│  Web (8186)     │
└────────┬────────┘
         │
         ├─ SitePageService
         ├─ SiteCollectionService
         ├─ SiteEntryService
         ├─ SiteMenuService
         └─ SiteSettingsService
         │
    (HTTP calls)
         │
┌────────▼────────────────────────┐
│  Domain CMS API (8190)           │
│  (single source of truth)        │
└─────────────────────────────────┘
```

**Entry points (Endpoints that web consumes):**
- `GET /api/v1/cms/public/settings` (cached 3600s)
- `GET /api/v1/cms/public/{lang}/pages` (cached 600s)
- `GET /api/v1/cms/public/{lang}/pages/{slug}` (cached 300s)
- `GET /api/v1/cms/public/{lang}/collections` (cached 600s)
- `GET /api/v1/cms/public/{lang}/collections/{key}` (cached 300s)
- `GET /api/v1/cms/public/{lang}/entries/{collectionKey}` (cached 180s)
- `GET /api/v1/cms/public/{lang}/entries/{collectionKey}/{slug}` (cached 180s)
- `GET /api/v1/cms/public/menus/{key}` (cached 600s)
- `GET /api/v1/cms/public/redirects` (cached 3600s)

All endpoints are **public** (no authentication required) and use `WEB_API_KEY` server-side.

---

## Future Migration Path (v0.3+)

**If TeatroMuseo expands to mobile or multiple websites:**

1. Create BFF layer (`ci4-bff-starter` → `teatro-bff-api`)
2. Migrate web to consume BFF instead of Domain
3. Build mobile app consuming same BFF
4. BFF aggregates:
   - Domain CMS API (pages, entries, collections)
   - Hub API (optional: user auth if needed)
   - Custom business logic (e.g., venue schedules, event listings)

**BFF endpoints would look like:**
```
GET /api/v1/web/pages/{slug}
GET /api/v1/mobile/pages/{id}  ← Different shape for mobile
GET /api/v1/web/events
```

---

## Decision Log

| Date | Decision | Rationale |
|------|----------|-----------|
| 2026-06-27 | Direct consumption for MVP | Single consumer, simpler deployment, TeatroMuseo v1.0 time-to-market |
| TBD (v0.3) | Introduce BFF if needed | Multiple frontends warrant aggregation |

---

## Owner

- **Decision Maker:** Product Owner (CMS Platform)
- **Implementer:** Web Development Team
- **Reviewers:** Domain API Team

---

## Related Documents

- `../../../ci4-website-builder-web/CLAUDE.md` — Web architecture
- `../../../ci4-website-builder-domain/CLAUDE.md` — Domain CMS architecture
- `../../../TASKS.md` — Roadmap (v0.3 features)

---

## Closure

✅ **This decision is FINAL for TeatroMuseo v1.0.**

The BFF pattern is a best practice for **multi-consumer** scenarios. For a single website consuming a single headless CMS, direct consumption is the pragmatic, efficient choice. Revisit this decision in v0.3 planning if scope expands.

**Next steps:**
- [ ] Deploy `ci4-website-builder-web` directly to Domain CMS
- [ ] Document API endpoints consumed in web's `Services/Site*.php`
- [ ] Schedule v0.3 planning review (TBD)
