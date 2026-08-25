# ADR-014 — External domain binding for CMS page types

- **Estado:** Aceptado
- **Fecha:** 2026-08-25
- **Alcance:** `ci4-website-builder-domain` y consumidores públicos del CMS

## Contexto

Un sitio puede entregar una página CMS desde un dominio externo, por ejemplo una ruta de
detalle cuyo contenido pertenece a una aplicación de dominio distinta. El CMS sigue siendo
dueño de la plantilla y de la configuración de la página, pero no debe inferir la URL a partir
de nombres de negocio ni acoplarse a una aplicación concreta.

## Decisión

El campo `page_type` identifica el contrato de renderizado y una configuración explícita de
binding asocia ese tipo con un dominio externo. El binding contiene únicamente configuración
de integración: `page_type`, `domain_key`, `base_url` y, cuando corresponda, el path o contrato
de resolución. El `domain_key` es un identificador estable de configuración, no una URL recibida
directamente desde una solicitud pública.

Los consumidores resuelven el binding mediante una allow-list de configuración por entorno.
No se acepta una URL arbitraria desde query string, payload CMS o contenido traducible. La
allow-list debe validar esquema y host antes de construir enlaces, y debe conservar el fallback
local cuando no exista binding para el `page_type`.

## Consecuencias

- Las plantillas CMS permanecen reutilizables entre sitios y dominios externos.
- La configuración de despliegue es la fuente de verdad de los hosts permitidos.
- Un nuevo binding requiere configuración y pruebas del consumidor; no requiere crear una nueva
  app de dominio ni copiar lectores de negocio.
- La invalidación de caché debe incluir tanto la página CMS como el dominio externo cuando el
  binding esté activo.

## Fuera de alcance

- No se agrega una tabla o migración para eventos, catálogo, museo u otra aplicación específica.
- No se resuelven dominios dinámicos desde datos editoriales sin validación de allow-list.
- No se modifica el contrato de autenticación: el Hub sigue siendo dueño de IAM y tokens.
