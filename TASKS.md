# TASKS — ci4-website-builder-domain

> Fuente de verdad para trabajo abierto en este repositorio.
> Los entregables cerrados están en [`TASKS_ARCHIVE.md`](TASKS_ARCHIVE.md).
> Seguimiento global: [`../TASKS.md`](../TASKS.md).
> Tracker depurado el 2026-07-21; no se conservan notas de conversación ni bitácoras de participantes.

## 🔴 En progreso

### Backport de mejoras de Teatro Museo (parte Domain)

> Plan completo (contexto, decisiones de alcance, todas las fases, todos los repos):
> [`../docs/plans/2026-08-24-plan-backport-teatromuseo.md`](../docs/plans/2026-08-24-plan-backport-teatromuseo.md).
> Tracker cross-repo: [`../TASKS.md`](../TASKS.md).

## 🟡 Próximo

### Backport de mejoras de Teatro Museo — fases posteriores (parte Domain)

- [ ] **BACKPORT-03-domain — Fase 3:** documentar convención de namespace de permisos
      `{app-code}.{resource}.{action}`; cablear kit de public-slugs sobre una entidad de
      referencia; abstracción compartida de `{entity}_translations`; capacidad de reordenamiento
      atómico por lotes (`SortOrderApiService` + `POST /{recurso}/sort-orders`); ADR de "external
      domain binding" para el patrón CMS `page_type` → dominio externo. Ver plan §Fase 3 — mayor
      pieza arquitectónica de esta fase, sin agregar nuevas apps de dominio.
- [ ] **BACKPORT-04-domain — Fase 4:** endpoints CMS compuestos (`layout`, `page-bootstrap/{path}`).
      Ver plan §Fase 4.

*(las fases Controller→Model y la auditoría de bloques owner-scoped quedaron cerradas; las
decisiones de producto pendientes se mantienen en el tracker global.)*

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
