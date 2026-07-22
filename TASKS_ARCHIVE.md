# TASKS_ARCHIVE — ci4-website-builder

> Historial de tareas completadas. Movido desde TASKS.md para mantener el tracker activo liviano.
> Última actualización: 2026-05-07

---

## ✅ Scaffold inicial + integración hub (Milestone domain-starter v0.1, 2026-05-07)

| ID | Descripción | Estado |
|---|---|---|
| DOM-001 | Scaffold base: clonado desde ci4-api-starter, eliminados módulos Auth/IAM/Users/Files/Identity/Admin. Agregados `Config\Hub`, `Config\DomainPermissions`, `HubClient`, `DomainAuthFilter` (alias `domainauth`), `SyncPermissions`, `Config\Scaffolding` override. Módulo Items de ejemplo generado con make-crud. PHPStan L8 limpio. | ✅ |
| DOM-002 | Integración end-to-end con hub: login → JWT → POST a domain → 201. Negative check: user sin permisos → 403. DomainAuthFilter llama `/auth/introspect` con `X-App-Key`, hub re-resuelve scope por `application_id`. | ✅ |
| DOM-003 | `domain:sync-permissions` rediseñado con `--admin-token` flag. `HubClient::registerPermission()` recibe bearer token explícito, corta en primer 401/403. `init.sh` actualizado para pedir JWT de setup. | ✅ |
| DOM-106 | README y README.es.md reescritos (~170 líneas). `docs/README.md` corregido. 12 docs de features del hub eliminados (stale clones). `docs/tech/jwt-auth.md` y `docs/architecture/AUTHENTICATION.md` reescritos como punteros al hub. | ✅ |

---

## ✅ Consumir base classes desde ci4-api-core (CORE-005, 2026-05-07)

24 archivos base eliminados de `app/`, 75 `use App\…` migrados a `dcardenasl\Ci4ApiCore\` vía sed batch. 3 architecture tests pure-core eliminados. PHPStan L8 + 202 tests verdes + CS-Fixer limpio. Smoke `make-crud Widget Demo` + `module:check` pasan.

---

## ✅ Consumo ci4-api-core v0.2.0 (2026-05-07)

Sin ID de tarea — trabajo derivado del runtime decoupling de ci4-api-core:
- Helpers procedurales, audit, HTTP filters, logging stack, mappers, support, `BaseRepository`, exception handlers, `Filterable`/`Searchable`/`QueryBuilder` consumidos desde `dcardenasl/ci4-api-core`
- `findByIds` implementado en `BaseRepository`
- Mapper acepta `object|array` (CORE-009)
- Fixtures de tests actualizados a imports de `dcardenasl/ci4-api-core`
- `composer.lock` regenerado

---

*TASKS_ARCHIVE · ci4-website-builder · 2026-05-07*

---

## 📦 Migrado desde `TASKS.md` — 2026-07-21

### Auditoría de traducciones y arquitectura

- **DOM-126** — corrección de presets de colecciones `news` y `portfolio`, fuente única de verdad,
  repair seeder y regresiones cubiertas.
- **DOM-125** — normalizador JSON compartido, endurecimiento de introspección de schemas y
  guardrails de dependencias de Controllers.
- **TRN-008** — auditoría de bloques por propietario, endpoint owner-scoped, aislamiento de
  propietarios, hijos incluidos y estados normalizados para el admin.
- **TRN-006** — estado `outdated` disponible en la auditoría global del dominio.
- **TRN-002** — resolución de nombres reales desde las traducciones, sin placeholders técnicos.
- **ARCH-DEEP-01** — separación de `FormService`, resolvers de uso y resolvers batch, con suite de
  calidad completa.

### Hardening y PHPStan

- **DEEP-BLOCK-01** — catálogo de bloques proyectado desde la fuente persistida y paridad de schemas.
- **DEEP-TRAN-01** — inyección explícita de dependencias en `TranslationAuditService` y catálogo de
  descriptores simples.
- **PHPSTAN-01..09** — baseline expandido drenado a cero, false-safety corregida, DTOs anotados,
  guardrails ajustados deliberadamente y suites unit/feature en verde.

### CMS y mantenimiento histórico

- **CMS-001..011** — bootstrap, schema, languages/settings, file translations, pages, menus,
  blocks, collections, entries, taxonomías, redirects y publishing programado.
- **DOM-101..111** y **BFF-107** — smoke tests, ADR de separación hub/domain, onboarding, permisos,
  validaciones, diagnóstico del hub, refactor de `HubClient` y documentación de extensiones.
- **DOM-112..124** — guardrail Controller→Model y migración progresiva de lógica a Services.

El tracker local queda sin backlog propio; las decisiones de producto y tareas cross-repo se
mantienen en `../TASKS.md`.
