# CLAUDE.md

Guidance for Claude Code when working in this repository.

## ⚡ Workflow — read this first

**Before touching any code, read `TASKS.md` in this directory.**

1. Take the first task from `## 🟡 Próximo`
2. Move it to `## 🔴 En progreso`
3. Work exclusively on that task — if anything is unclear, ask before implementing
4. When done: move it to `## ✅ Completadas` with one line of notes (what you did and why)
5. Never work on tasks not defined in TASKS.md without explicit confirmation

For cross-repo context, read `../TASKS.md`.

---

## What this is

`ci4-website-builder` is a CodeIgniter 4 **website builder** template. It owns its own
business logic and database tables, but **delegates auth and IAM to a central
hub** — a separate `ci4-api-starter` instance that stores users, applications,
roles and permissions.

The split:

```
Browser/SPA → Website Builder (here)    → Database (this app's tables)
                ↓ JWT validation
              Hub (ci4-api-starter)    → Database (users, roles, perms)
                ↑ Service token (M2M)
              Website Builder ──────────┘
```

**Boundaries:**

- Website builder app **never issues JWTs**. The hub does.
- Website builder app **validates JWTs** by calling `POST /api/v1/auth/introspect` on the hub.
- Website builder app **registers its permissions** in the hub via `POST /api/v1/iam/self-permissions`
  using its own X-App-Key (`hub.apiKey`). No superadmin JWT required for the primary registration.
  `--admin-token` is only needed when `--mirror-to-self` or `--assign-to-role` is also set.
- Website builder app **does not store users**. There is no `users` table here.

## Essential commands

```bash
# Dev server (default port 8090 to avoid colliding with hub on :8080 / admin on :8082)
# IMPORTANT: CI4 spark serve requires a SPACE before the port — equals sign is silently ignored:
#   php spark serve --port 8090   ✅
#   php spark serve --port=8090   ❌ (starts on :8080 without warning, collides with hub)
php spark serve --port 8090

# Tests
# Prefer `composer test*` (passes --no-coverage). Bare `vendor/bin/phpunit` triggers a
# harmless XDEBUG_MODE=coverage warning (phpunit.xml declares a <coverage> block for
# test:coverage but xdebug isn't active by default) and returns a non-zero exit code on
# an otherwise-passing run — add --no-coverage yourself if running it directly.
vendor/bin/phpunit
vendor/bin/phpunit tests/Unit
vendor/bin/phpunit tests/Integration
vendor/bin/phpunit tests/Feature

# Quality gates
composer quality          # phpstan + cs-check + phpunit
composer cs-fix           # auto-fix style

# Database
php spark migrate         # idempotency_keys, audit_logs, request_logs, metrics, jobs
php spark db:seed SiteBootstrapSeeder # OPTIONAL demo content (languages, settings, pages, menus, blocks) — NOT required. See README.md "Required vs. optional bootstrap".

# Hub permission sync (idempotent — safe to rerun). Needs a superadmin JWT.
php spark domain:sync-permissions --admin-token=<jwt>     # or set hub.adminToken in .env

# CRUD scaffolding (always use the shell wrapper)
bash vendor/bin/make-crud.sh ResourceName DomainName 'field1:type,field2:type' yes
```

## Architecture cheat sheet

The DTO-first layered pattern is identical to ci4-api-starter:

```
Controller → [RequestDTO] → Service → Model → Entity → [ResponseDTO]
```

Base classes live in `dcardenasl/ci4-api-core` (path repo `../ci4-api-core`,
declared in `composer.json` and consumed under `vendor/dcardenasl/ci4-api-core/`).
Generated and hand-written code imports them directly from the package namespace:

- `dcardenasl\Ci4ApiCore\Http\ApiController` — declarative `handleRequest()` orchestration
- `dcardenasl\Ci4ApiCore\Services\BaseCrudService` — pure, transactional service layer
- `dcardenasl\Ci4ApiCore\Dto\BaseRequestDTO` — auto-validating DTOs
- `dcardenasl\Ci4ApiCore\Models\BaseAuditableModel` — local audit logging via `Auditable` trait

What's **different** here:

- `App\Filters\DomainAuthFilter` (alias `domainauth`) replaces `JwtAuthFilter`. It
  calls `HubClient::introspect()` and injects `(uid, permissions[])` into
  `ApiRequest::setAuthContext()` and `ContextHolder` so `PermissionFilter` works
  unchanged.
- `App\Libraries\Hub\HubClient` is the only place that talks to the hub. It
  handles introspection caching (TTL configurable via `hub.introspectCacheTtl`)
  and service-token caching (refreshed `serviceTokenSafetyMargin` seconds before
  expiry).
- `App\Commands\SyncPermissions` (`php spark domain:sync-permissions`) registers
  every permission in `Config\DomainPermissions::PERMISSIONS` using the domain's
  own `X-App-Key` via `POST /api/v1/iam/self-permissions` — no superadmin JWT needed
  for the primary registration. Use `--mirror-to-self --admin-token=<jwt>` to also
  register the permissions under hub app `self` (application_id=1) for admin UI gating.
  The CRUD scaffolder appends the standard `{resource}.read/write/delete` entries automatically.
- `Config\Scaffolding` overrides `protectedRouteFilters` to
  `['domainauth', 'permission:items.read', 'throttle']` — generated CRUDs are
  protected by `domainauth` automatically.

## Adding a new permission

1. Edit `app/Config/DomainPermissions.php` only for manual/legacy permissions —
   the CRUD scaffold appends the standard `{resource}.read/write/delete`
   entries automatically for new resources.
2. Run `php spark domain:sync-permissions` — registers in the hub (idempotent).
3. In the hub admin panel, attach the new permission to the role(s) that should
   carry it.
4. Use the code in your filter argument: `permission:items.archive`.

## Adding a new CRUD module

```bash
bash vendor/bin/make-crud.sh Item Example 'name:string:required|searchable,description:text' yes
php spark migrate
pkill -f 'spark serve'; php spark serve --port 8090 &
```

The generator emits routes already wrapped in `domainauth + permission:items.read + throttle`
— no manual filter wiring needed. Update the route filters per HTTP verb if your
module needs distinct read/write codes.

## Required environment variables

| Variable | Purpose |
|---|---|
| `hub.url` | Base URL of the hub (e.g. `http://localhost:8080`) |
| `hub.apiKey` | X-App-Key bound to this app's `applications` row in the hub |
| `hub.appCode` | Application code as registered in the hub |
| `hub.introspectCacheTtl` | (optional) TTL in seconds for cached introspect responses, default 30 |
| `hub.adminToken` | (optional) Superadmin JWT. Only needed when running `domain:sync-permissions --mirror-to-self` or `--assign-to-role`. |
| `database.default.*` | Website builder app's own MySQL connection |
| `encryption.key` | CI4 encryption key (32 bytes after `hex2bin:` decode) |

## Setup prerequisite

`init.sh` runs `php spark domain:sync-permissions --admin-token=<jwt>` against the hub.
The primary permission registration uses the domain's own X-App-Key (`hub.apiKey`) via
`POST /api/v1/iam/self-permissions` — **no superadmin JWT required** for this step.

The call requires:

1. The hub is running and reachable.
2. An entry in the hub's `applications` table with `code = hub.appCode`.
3. An API key in the hub bound to that application (see `php spark apps:bootstrap`
   on the hub side). Kickstart sets this automatically during `DOMAIN_BOOTSTRAP`.
4. `--admin-token` is required only when `--mirror-to-self` or `--assign-to-role`
   is set. The hub gates `POST /api/v1/iam/permissions` (used for the mirror) on
   `iam.superadmin-access`. Obtain via `POST /api/v1/auth/login` as superadmin.

To also register permissions under hub app `self` (`application_id = 1`) for admin
UI gating, pass `--mirror-to-self`. Kickstart controls this via `mirror_to_hub: true`
in `template.json`.

You can re-run `domain:sync-permissions` at any time — it is idempotent.

## Static analysis

PHPStan runs at level 8 with a `phpstan-baseline.neon` that tracks historical type-debt.
**Rule:** the baseline entry count can only decrease. New code must not introduce new errors.
Current count: **0 baselined entries** (drained on 2026-07-15 — clean slate). Keep it
that way: new code must ship with zero PHPStan errors and no new baseline entries.

Run before pushing:

```bash
composer quality
```

## Architecture guardrail tests

`tests/Unit/Architecture/` is the authoritative source for structural rules this
codebase enforces beyond PHPStan — always check it before assuming a pattern is
"just a convention." Two rules of this shape exist and both follow the same
ratchet philosophy (a violation can shrink or disappear silently, but growing
one or adding a new offender requires editing the test's `BASELINE`/assertion
deliberately, with a dated comment):

- `ServiceModelDependencyConventionsTest` — Services (`app/Services/**`) must not
  call `model()`, import `App\Models\*`, or call `Database::connect()` directly;
  use the injected repository (`$this->repository`/constructor-injected
  `RepositoryInterface`) instead. Currently has baselined exceptions (documented
  per-file, per-pattern).
- `ControllerModelDependencyConventionsTest` — same three patterns, scoped to
  `app/Controllers`. **Zero-tolerance as of 2026-07-21** (DOM-112..DOM-124) — no
  baselined exceptions remain. A Controller must delegate to a Service; see
  `PublicEntryController` for the reference shape (constructor-injected
  interface, `resolveDefaultService()`, action methods that only call the
  service and shape the response).

Both patterns are regex scans over token-stripped source (comments/strings
removed first), so they also silently miss `new App\Models\X()` (no `use`
import, no `model()` call) — an already-accepted escape hatch used a few places
in this codebase specifically to get a concretely-typed Model instance for a
custom method the generic `RepositoryInterface`/`getModel(): \CodeIgniter\Model`
return type doesn't expose (e.g. `BlockInstanceService::blockTypeById()`,
`*Service::isSlugAvailable()`). Don't reach for that pattern to dodge the
guardrail on a *new* violation — it's for this one specific typing problem, not
a loophole.

## Common pitfalls

- ❌ Issuing JWTs from this app — that's the hub's job, always.
- ❌ Calling `Services::userModel()` or any IAM service — those only exist in the hub.
- ❌ Storing tokens in cookies/localStorage on the client side — use PHP sessions
  (admin/web layer) or pass via Authorization header (SPAs).
- ❌ Hardcoding permission strings — use `DomainPermissions::PERMISSIONS` and
  `domain:sync-permissions` so the hub stays in sync.
- ❌ **Running `php spark migrate` (or any migration/refresh command) without checking which
  database you're targeting.** With no `-g` flag it targets `database.default` — the persistent
  **dev** database — not `database.tests`. Test-only workflows must use
  `php spark tests:prepare-db` (which explicitly connects to the `tests` group) or pass
  `-g tests` yourself. Running an untargeted `migrate --all`/`migrate:refresh` against a database
  you didn't mean to touch can drop and recreate its schema empty. If this happens, the app keeps
  working (see "Required vs. optional bootstrap" in README.md) — just re-run
  `db:seed SiteBootstrapSeeder` to restore the demo content, or accept the empty state and rebuild
  through the admin UI. There is no way to recover data that wasn't part of that seeder.
- ❌ **Typing `php spark db:seed SeederName -g tests` expecting it to target the tests database.**
  Unlike `migrate`, CI4's stock `db:seed` command does not read any `-g`/`--group` option — it
  always seeds `database.default` (dev), silently ignoring the flag with no warning. There is no
  CLI-level way to seed the `tests` group directly. If you need test data, either seed inside a
  `CIUnitTestCase`-based test (which switches the DB group itself), or write a dedicated command
  like `PrepareTestDatabase` that calls `Database::connect('tests')` explicitly. (Root-caused
  2026-07-18 after this exact pattern produced a seemingly "flaky" row count in an earlier audit —
  see the monorepo root's docs/audits/2026-07-10-hardening-execution-log.md.)
- ❌ **Shallow `(array) $entity->someJsonField` casting on any Entity property cast as
  `'json'`** (`schema_definition`, `wizard_config`, `block_template`, etc. — grep
  Entities for `=> 'json'` to find the full list). CI4's `json` cast decodes to
  `stdClass` **recursively at every nesting level**, not just the top one — a
  shallow cast only converts the outer object, leaving nested values (e.g.
  `$schema['fields']['heading']`) as `stdClass`, which then silently fail any
  downstream `is_array()` check and get treated as empty. Always go through
  `App\Libraries\Cms\JsonCastNormalizer::toArray()` instead, which handles the
  string/object/array shapes correctly via a full `json_encode`/`json_decode`
  round-trip. (Root-caused 2026-07-21 during DOM-122: this exact bug shipped in
  `WizardConfigService`'s first draft and would have made the wizard's block
  editor always see empty `fields`/`config_fields` — caught only because a
  characterization test with real fixtures was written before trusting the
  refactor. `BlockSchemaIntrospector::introspect()` now self-normalizes via this
  helper too, so passing it a raw un-normalized Entity property can no longer
  silently misbehave.)

## Where to read next

- `../ci4-api-starter/CLAUDE.md` — the hub's API patterns + service-token / introspect contracts.
- `vendor/dcardenasl/ci4-api-core/docs/ARCHITECTURE_CONTRACT.md` — DTO-first patterns enforced by the scaffolding engine (or `../ci4-api-core/docs/ARCHITECTURE_CONTRACT.md` while the path repo is symlinked).
