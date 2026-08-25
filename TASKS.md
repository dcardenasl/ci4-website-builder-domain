# TASKS — ci4-website-builder-domain

> Fuente de verdad para trabajo abierto en este repositorio.
> Los entregables cerrados están en [`TASKS_ARCHIVE.md`](TASKS_ARCHIVE.md).
> Seguimiento global: [`../TASKS.md`](../TASKS.md).
> Tracker depurado el 2026-07-21; no se conservan notas de conversación ni bitácoras de participantes.

## 🔴 En progreso

### Remediación de huecos profundos (parte Domain)

> Plan completo: [`../docs/plans/2026-08-25-plan-remediacion-huecos-profundos.md`](../docs/plans/2026-08-25-plan-remediacion-huecos-profundos.md).
> Auditoría origen: [`../docs/audits/2026-08-25-auditoria-profunda-backport-git-history.md`](../docs/audits/2026-08-25-auditoria-profunda-backport-git-history.md).
> Tracker cross-repo: [`../TASKS.md`](../TASKS.md).

- [ ] **GAP-04-domain:** ítems del plan §Fase 4, revisados 2026-08-25 contra el criterio de
      alineación. La mayoría tiene "triple señal" (existen independientemente en
      cms/catalog/event-domain de Teatro Museo — máxima confianza de genericidad). Alta
      prioridad: rate-limit por `X-App-Key` en vez de IP (reencuadrado como práctica
      correcta en general para callers server-to-server, no solo hosting compartido), completar
      el endurecimiento de `HubClient` (el backport anterior tomó solo la mitad), bug de ventana
      de publicación en `SlugRouter`, `HubSignatureFilter` ausente (verificación de origen en
      llamadas Hub→domain), split `hub.publicUrl`/`FileUrlResolver` (mismo incidente que
      `project_media_url_portability_fix_2026-08-09.md` mencionaba cerrado, pero solo se cerró
      en teatromuseo), `Config\Cache::$prefix` vacío (riesgo de colisión entre apps hermanas).
      Modificados por la revisión de alineación: el outbox transaccional de invalidación se
      porta como **capacidad opcional documentada**, no reemplaza el enqueue directo por
      defecto; las facetas para listados y `PageQualityService` se portan **junto con un
      consumidor de referencia real** (no como tabla/servicio sin nada que los use); `apcu`
      queda detrás de `CACHE_HANDLER` en `.env.example`, nunca default. Resto sin cambios:
      telemetría de lecturas públicas (si se le agrega consumidor), proyecciones admin
      set-based, bloque `entry_reference`, `BlockInstancePurger`, sort-orders para más recursos,
      `cms:repair-slugs`, refactor de servicios a modelos (todo o nada).

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
