# TASKS — ci4-website-builder-domain

> Trabajo abierto de este repositorio. Lo cerrado está en [`TASKS_ARCHIVE.md`](TASKS_ARCHIVE.md).
> Plan cross-repo: [`../docs/plans/2026-09-11-plan-nivelacion-stack-modular-suite.md`](../docs/plans/2026-09-11-plan-nivelacion-stack-modular-suite.md).

## 🔴 En progreso

*(vacío; CNV-007-D0 y CNV-007-D1…D4 están cerradas)*

## 🟡 Próximo

- [ ] **CNV-007-F9 — Autorización por recurso.** Diseñar después de cerrar la nivelación completa,
      con contrato común y matriz de pruebas cross-repo.

## ✅ Cerrado con evidencia

- **CNV-007-D0 — Baseline contractual.** `composer quality` verde, contrato de documento/patch/
  preview/fallback/límites/ownership verificado y OpenAPI regenerado.
- **GAP-02-Domain — Paginación de auditoría.** Commit `1029e46`; reportes paginados por lotes,
  filtros acotados, orden estable, `meta` contractual y regresiones reales del servicio.

## ⚪ Fuera del plan actual

- [ ] **TRN-006** — estados editoriales, permisos y controles de publicación.

## 🏗️ Contratos

- Servicios puros, DTO-first y Controllers/adaptadores delgados.
- Migraciones nuevas; PHPStan sin baseline nuevo; tests de regresión junto a cada cambio.
- No introducir autorización por recurso antes de la fase final cross-repo.

## 🔧 Referencias

- Plan: [`../docs/plans/2026-09-11-plan-nivelacion-stack-modular-suite.md`](../docs/plans/2026-09-11-plan-nivelacion-stack-modular-suite.md)
- Histórico: [`TASKS_ARCHIVE.md`](TASKS_ARCHIVE.md)
