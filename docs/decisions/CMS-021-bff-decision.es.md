# CMS-021: Decisión arquitectónica de Backend-for-Frontend (BFF)

**Fecha:** 2026-06-27  
**Estado:** ✅ **DECIDIDO — Consumo directo**  
**Componentes afectados:** Domain CMS, sitio público, Admin

## Pregunta

¿Un sitio público generado debe consumir directamente la API del Domain CMS o usar la capa BFF
opcional que agrega las APIs de Domain y Hub?

## Decisión

Para un sitio con un solo consumidor público, el consumo directo del Domain es el valor por
defecto. El BFF opcional se activa cuando múltiples frontends, formas de respuesta materialmente
distintas o responsabilidades relevantes de agregación y proxy justifican una frontera adicional.

## Justificación

El consumo directo mantiene un sitio simple en dos aplicaciones, evita un salto de red innecesario
y deja el comportamiento específico de presentación en Web. Un BFF es apropiado cuando varios
clientes comparten los mismos datos o cuando agregación, autenticación M2M, proxy de webhooks o
gestión de límites de velocidad forman una frontera reutilizable.

## Ruta de migración genérica

1. Activar la app opcional `ci4-website-builder-bff` en el puerto `8188`.
2. Apuntar Web al BFF y mantener Domain como fuente de verdad del CMS.
3. Agregar rutas genéricas de proxy o lecturas compuestas solo cuando el contrato del consumidor
   las requiera.
4. Mantener el contenido del sitio, los módulos de negocio y las reglas específicas del tenant en
   el sitio/domain generado, no en el BFF del starter.

## Contratos relacionados

- `bff.hubUrl` y `bff.domainUrl` identifican los upstreams; `BFF_ALLOWED_ORIGINS` es explícito.
- El BFF reenvía la autenticación Bearer; no emite JWT ni posee almacenamiento de usuarios.
- `PublicReadSupport` es read-only, opt-in y está desactivado por defecto.
- Web conserva un fallback directo a Domain para despliegues sin BFF.
