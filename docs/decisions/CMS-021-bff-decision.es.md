# CMS-021: Decisión Arquitectónica — Backend-for-Frontend (BFF)

**Fecha:** 2026-06-27  
**Estado:** ✅ **DECIDIDO — Consumo Directo**  
**Componentes Afectados:** Domain CMS, Public Website, Admin  
**Hito:** TeatroMuseo v1.0 (MVP)

---

## Pregunta

¿Debe `ci4-website-builder-web` (sitio público) consumir directamente la API del Domain CMS (`ci4-website-builder-domain`), o debería haber una capa intermedia **Backend-for-Frontend (BFF)** (`ci4-bff-starter`) que agregue las APIs del Domain + Hub y exponga una API web especializada?

---

## Contexto

- **ci4-website-builder-web** actualmente habla con la API del Domain CMS para páginas, entradas, menús, configuración
- **ci4-bff-starter** existe como plantilla para patrones de agregación multi-consumidor
- **TeatroMuseo** es la primera aplicación de producción usando este CMS
- **Alcance:** Sitio único consumiendo una única API headless (Domain CMS)
- **Futuro:** Potencialmente múltiples frontends (apps móviles, segundo sitio) en v0.3+

---

## Decisión: ✅ **CONSUMO DIRECTO**

**Para TeatroMuseo v1.0 MVP:** El sitio público (`ci4-website-builder-web`) **consume directamente** la API del Domain CMS.  
El patrón BFF se aplaza a **v0.3 o posterior** si/cuando múltiples frontends necesiten APIs agregadas.

---

## Justificación

### ✅ El Consumo Directo es Óptimo para MVP
| Aspecto | Directo | BFF | Veredicto |
|--------|---------|-----|-----------|
| **Complejidad de despliegue** | ✓ Más simple (2 apps) | ✗ Más (3 apps) | Directo gana |
| **Latencia de solicitud** | ✓ ~50ms | ✗ ~80-100ms | Directo gana |
| **Transformación de datos** | ✓ En capa web | ✗ En capa BFF | Empate (misma complejidad) |
| **Reutilización de código** | ✓ Web posee lógica específica de UI | ✗ Duplicada en BFF | Directo gana |
| **Escenario de consumidor único** | ✓ Optimizado | ✗ Sobre-ingeniería | Directo gana |

### 🔄 Cuándo Introducir BFF
Introduzca una capa BFF solo cuando **las tres** condiciones siguientes sean verdaderas:
1. **Múltiples frontends** necesitan la misma API (app móvil + web, o segundo sitio)
2. **Las diferencias de contrato de API** son significativas (móvil necesita formas de respuesta diferentes)
3. **La lógica de agregación** es lo suficientemente compleja para justificar una capa separada (autenticación M2M, proxying de webhooks, gestión de límites de velocidad)

**Estado actual:** Solo 1 frontend (web) → BFF añade complejidad innecesaria.

---

## Implementación para TeatroMuseo

**Arquitectura:**
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
    (Llamadas HTTP)
         │
┌────────▼────────────────────────┐
│  Domain CMS API (8190)           │
│  (fuente única de verdad)        │
└─────────────────────────────────┘
```

**Puntos de entrada (Endpoints que consume web):**
- `GET /api/v1/cms/public/settings` (cached 3600s)
- `GET /api/v1/cms/public/{lang}/pages` (cached 600s)
- `GET /api/v1/cms/public/{lang}/pages/{slug}` (cached 300s)
- `GET /api/v1/cms/public/{lang}/collections` (cached 600s)
- `GET /api/v1/cms/public/{lang}/collections/{key}` (cached 300s)
- `GET /api/v1/cms/public/{lang}/entries/{collectionKey}` (cached 180s)
- `GET /api/v1/cms/public/{lang}/entries/{collectionKey}/{slug}` (cached 180s)
- `GET /api/v1/cms/public/menus/{key}` (cached 600s)
- `GET /api/v1/cms/public/redirects` (cached 3600s)

Todos los endpoints son **públicos** (sin autenticación requerida) y usan `WEB_API_KEY` del lado del servidor.

---

## Ruta de Migración Futura (v0.3+)

**Si TeatroMuseo se expande a móvil o múltiples sitios:**

1. Crear capa BFF (`ci4-bff-starter` → `teatro-bff-api`)
2. Migrar web para consumir BFF en lugar de Domain
3. Construir app móvil consumiendo el mismo BFF
4. BFF agrega:
   - API del Domain CMS (páginas, entradas, colecciones)
   - API del Hub (opcional: autenticación de usuario si es necesaria)
   - Lógica empresarial personalizada (ej: horarios de venue, listados de eventos)

**Los endpoints de BFF se verían así:**
```
GET /api/v1/web/pages/{slug}
GET /api/v1/mobile/pages/{id}  ← Forma diferente para móvil
GET /api/v1/web/events
```

---

## Registro de Decisiones

| Fecha | Decisión | Justificación |
|------|----------|-----------|
| 2026-06-27 | Consumo directo para MVP | Consumidor único, despliegue más simple, comercialización rápida de TeatroMuseo v1.0 |
| TBD (v0.3) | Introducir BFF si es necesario | Múltiples frontends justifican la agregación |

---

## Propietario

- **Tomador de Decisiones:** Product Owner (CMS Platform)
- **Implementador:** Web Development Team
- **Revisores:** Domain API Team

---

## Documentos Relacionados

- `../../../ci4-website-builder-web/CLAUDE.md` — Arquitectura web
- `../../../ci4-website-builder-domain/CLAUDE.md` — Arquitectura del Domain CMS
- `../../../TASKS.md` — Roadmap (características v0.3)

---

## Cierre

✅ **Esta decisión es FINAL para TeatroMuseo v1.0.**

El patrón BFF es una mejor práctica para escenarios **multi-consumidor**. Para un sitio único consumiendo un único CMS headless, el consumo directo es la opción pragmática y eficiente. Revise esta decisión en la planificación de v0.3 si el alcance se expande.

**Próximos pasos:**
- [ ] Desplegar `ci4-website-builder-web` directamente a Domain CMS
- [ ] Documentar endpoints de API consumidos en `Services/Site*.php` de web
- [ ] Programar revisión de planificación de v0.3 (TBD)
