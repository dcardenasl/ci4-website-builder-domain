# TASKS — ci4-website-builder-domain

> Trabajo abierto de este repositorio. Lo cerrado está en [`TASKS_ARCHIVE.md`](TASKS_ARCHIVE.md).
> Plan cross-repo: [`../docs/plans/2026-09-11-plan-nivelacion-stack-modular-suite.md`](../docs/plans/2026-09-11-plan-nivelacion-stack-modular-suite.md).

## 🔴 En progreso

*(vacío; F9 quedó archivada en `TASKS_ARCHIVE.md`)*

## 🟡 Próximo

*(vacío; la autorización por recurso es la última fase funcional del plan CNV-007)*

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
- F9 mantiene el modelo single-tenant v1: identidad y permisos globales vienen de Hub; el alcance
  concreto vive en Domain y no se cachea. El diseño completo está en `docs/adr/ADR-015-RESOURCE-AUTHORIZATION.md`.

## 🔧 Referencias

- Plan: [`../docs/plans/2026-09-11-plan-nivelacion-stack-modular-suite.md`](../docs/plans/2026-09-11-plan-nivelacion-stack-modular-suite.md)
- Histórico: [`TASKS_ARCHIVE.md`](TASKS_ARCHIVE.md)
