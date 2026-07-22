# TASKS — ci4-website-builder-domain

> Fuente de verdad para trabajo abierto en este repositorio.
> Los entregables cerrados están en [`TASKS_ARCHIVE.md`](TASKS_ARCHIVE.md).
> Seguimiento global: [`../TASKS.md`](../TASKS.md).
> Tracker depurado el 2026-07-21; no se conservan notas de conversación ni bitácoras de participantes.

## 🔴 En progreso

*(vacío)*

## 🟡 Próximo

*(vacío — las fases Controller→Model y la auditoría de bloques owner-scoped quedaron cerradas;
las decisiones de producto pendientes se mantienen en el tracker global.)*

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
