# TASKS — ci4-website-builder-domain

> Trabajo abierto de este repositorio. Lo cerrado está en [`TASKS_ARCHIVE.md`](TASKS_ARCHIVE.md).
> Plan cross-repo: [`../docs/plans/2026-09-11-plan-nivelacion-stack-modular-suite.md`](../docs/plans/2026-09-11-plan-nivelacion-stack-modular-suite.md).

## 🔴 En progreso

*(vacío; CNV-007-D1…D4 ya están cerradas)*

## 🟡 Próximo

- [ ] **CNV-007-D0 — Baseline contractual.** Ejecutar `composer quality`, tests de endpoint e
      integración, y verificar documento, patch, `409`, rollback, preview, fallback, límites y
      ownership antes de habilitar Web/Admin.
- [ ] **GAP-02-Domain — Paginación de auditoría de traducciones.** Añadir contrato versionado de
      `page`/`limit`/`meta`, límites seguros, consulta acotada, orden determinista y regresiones;
      no construir informes ilimitados en memoria.
- [ ] **CNV-007-F9 — Autorización por recurso.** Diseñar después de cerrar la nivelación completa,
      con contrato común y matriz de pruebas cross-repo.

## ⚪ Fuera del plan actual

- [ ] **TRN-006** — estados editoriales, permisos y controles de publicación.

## 🏗️ Contratos

- Servicios puros, DTO-first y Controllers/adaptadores delgados.
- Migraciones nuevas; PHPStan sin baseline nuevo; tests de regresión junto a cada cambio.
- No introducir autorización por recurso antes de la fase final cross-repo.

## 🔧 Referencias

- Plan: [`../docs/plans/2026-09-11-plan-nivelacion-stack-modular-suite.md`](../docs/plans/2026-09-11-plan-nivelacion-stack-modular-suite.md)
- Histórico: [`TASKS_ARCHIVE.md`](TASKS_ARCHIVE.md)
