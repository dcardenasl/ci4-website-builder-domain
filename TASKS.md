# TASKS — ci4-website-builder-domain

> Fuente de verdad para trabajo abierto en este repositorio.
> Los entregables cerrados están en [`TASKS_ARCHIVE.md`](TASKS_ARCHIVE.md).
> Seguimiento global: [`../TASKS.md`](../TASKS.md).
> Tracker depurado el 2026-07-21; no se conservan notas de conversación ni bitácoras de participantes.

## 🔴 En progreso

*(vacío)*

## ✅ Completadas

### F.0 — Cierre de GAPs previos a la app unificada (2026-08-26)

> Contexto: [`../docs/plans/2026-08-25-plan-app-unificada.md`](../docs/plans/2026-08-25-plan-app-unificada.md) §7 F.0 (decisión D8).
> Plan origen: [`../docs/plans/2026-08-25-plan-remediacion-huecos-profundos.md`](../docs/plans/2026-08-25-plan-remediacion-huecos-profundos.md).

- [x] **GAP-04-8 — ventana de publicación.** `SlugRouter` filtraba solo por `status = 'published'`
      y ningún camino de lectura pública de páginas comprobaba `published_at`: la columna la
      escribía `ScheduledPublishingJob` pero era decorativa para el control de acceso, así que una
      página marcada publicada con `published_at` futuro era alcanzable por URL directa. Las
      entradas **sí** aplicaban la ventana, con la regla duplicada inline dos veces en
      `PublicEntryReader`. Nuevo `App\Libraries\Cms\PublicationWindow` como definición única,
      aplicado en los 4 puntos de control de acceso de páginas (`SlugRouter` ×2,
      `PublicPageReader` ×2) y consumido también por las 2 copias de entradas, que dejan de estar
      duplicadas. 3 tests de regresión (verificados en rojo antes del fix) + 8 unitarios de la
      regla. No se aplica a `PublicCollectionReader::resolveCollectionIndexPage()` ni a
      `MenuItemService::getCollectionPrefix()`: resuelven el prefijo canónico de URL de una
      colección, no si algo puede servirse — hacer la construcción de prefijos dependiente del
      tiempo haría que URLs de entradas ya publicadas saltaran al slug de respaldo mientras su
      página índice espera la ventana. Documentado en el docblock de `PublicationWindow`.

- [x] **GAP-04-20 — refactor de pivots de taxonomía a modelos.** `EntryService` escribía
      `cms_entry_categories` y `cms_entry_tags` con `Database::connect()` directo. Nuevos
      `EntryCategoryModel` / `EntryTagModel` (clave primaria compuesta, sin `id` sustituto, por eso
      no extienden `BaseAuditableModel` — registrados en `NON_AUDITABLE` con justificación) y
      `EntryCategoryLinkRepository` / `EntryTagLinkRepository` tras
      `EntryTaxonomyLinkRepositoryInterface`, inyectados como dependencias **requeridas** vía
      `CmsDomainServices`. El ratchet de `ServiceModelDependencyConventionsTest` baja: `EntryService`
      pasa de `db_connect => 3` a `db_connect => 1`. Los `model_call` restantes son validación de
      existencia contra otros agregados, no acceso a tablas propias. 5 tests de integración.

### Diferido a la app unificada (no se porta desde aquí)

De los 21 ítems de §Fase 4 del plan de remediación, **14 ya estaban cerrados** (verificado contra
el código el 2026-08-26): rate-limit por `X-App-Key`, endurecimiento de `HubClient`, dashboard
permission-aware, proyecciones set-based, `AdminListProjectionDecoder`, `HubSignatureFilter`,
split `hub.publicUrl`, `Config\Cache::$prefix`, `CACHE_HANDLER`, `EntryListingContentResolver`,
`BlockInstancePurger`, `PageQualityService`, sort-orders extendidos, `cms:repair-slugs`.

Los 5 restantes **no se hacen aquí**:

- **Outbox transaccional de invalidación (4):** descartado. Un outbox garantiza entrega *entre
  procesos*; en la app unificada la invalidación es una llamada en proceso dentro del mismo
  request. No hay entrega que garantizar.
- **`PublicReadTelemetry` (2):** difierido. Es responsabilidad del módulo de observabilidad de la
  app nueva, no un filtro del dominio.
- **Bloque `entry_reference` (13), facetas materializadas (14), importación de submissions (21):**
  diferidos. Features nuevas del CMS; construirlas aquí y portarlas después es hacerlas dos veces.

## 🟡 Próximo

*(vacío)*

## ⚪ Backlog

*(vacío)*

## 🏗️ Contratos de arquitectura

- **DTO-First:** todo Controller in/out usa DTOs; evitar arrays sin contrato.
- **Services puros:** no conocen HTTP; reciben DTOs y devuelven DTOs o excepciones de dominio.
- **Controllers delgados:** usar `ApiController::handleRequest()`.
- **Autenticación:** este repositorio delega introspección y emisión de tokens al hub.
- **HubClient:** es el único punto de comunicación con el hub.
- **Permisos:** usar separador `.` y rutas por dominio en `app/Config/Routes/v1/`.
- **No tabla users:** usuarios e IAM viven en el hub.
- **Tests:** todo endpoint nuevo necesita Feature test.
- **CRUD nuevo:** preferir `php spark make:crud {Resource} --domain {Domain} --route {slug}`.
- **Calidad:** ejecutar `composer quality` antes de cerrar una tarea.

## 🔧 Referencias

- Histórico: [`TASKS_ARCHIVE.md`](TASKS_ARCHIVE.md)
- Tracker global: [`../TASKS.md`](../TASKS.md)
