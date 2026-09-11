# ADR-015 — Autorización por recurso en Domain

**Estado:** aceptado
**Fecha:** 2026-09-11
**Alcance:** páginas, entradas, colecciones y sus instancias de bloque

## Contexto

Hub es la fuente de identidad, roles y permisos globales, pero no debe conocer la
estructura editorial de cada aplicación. La autorización global (`cms.*`) por sí
sola permitía que un usuario con acceso al módulo consultara o modificara cualquier
recurso editorial. La autorización por recurso se incorpora al final de CNV-007,
sin introducir multi-tenancy física en la versión single-tenant v1.

## Decisión

Domain mantiene `cms_resource_access`, con una fila única por
`resource_type/resource_id/user_id` y niveles `read`, `write` y `admin`. Los grants
no tienen FK al Hub: el identificador de usuario se valida en el límite de identidad
y Domain conserva únicamente la relación de alcance.

- Un grant sobre una colección se hereda a sus páginas y entradas.
- Crear un recurso desde una petición autenticada asigna `admin` al actor.
- El `admin` del recurso puede delegar `read` y `write`; solo superadmin puede
  delegar otro `admin`.
- Nunca se permite revocar al último administrador; el cambio de dueño es transaccional.
- El alcance se consulta en cada petición. No se cachea, de modo que una revocación
  sea efectiva en la siguiente petición.
- Un recurso inexistente, eliminado o fuera de alcance responde con el mismo 404 para
  evitar enumeración. La denegación se registra como `authorization_denied_resource`
  sin hacer depender la decisión del transporte de auditoría.
- El filtro de listados se ejecuta en el repositorio SQL mediante `EXISTS`; los
  listados completos y las proyecciones paginadas comparten la misma política.
- La URL de las rutas anidadas de bloques es la fuente autoritativa del owner; el body
  no puede mover ni crear bloques bajo otro recurso.
- El preview público sigue siendo una proyección bearer firmada. Su token solo se
  entrega después de cargar el documento protegido; el endpoint público no recibe
  contexto de usuario ni se convierte en un bypass del control editorial.

## Consecuencias

La autorización se concentra en una interfaz/servicio de Domain y sus adaptadores,
por lo que Admin, Web y BFF no duplican reglas. Los endpoints agregados de auditoría
siguen reservados a superadmin mientras no exista una proyección agregada con alcance;
los endpoints de recurso y owner sí aplican el grant concreto. La matriz de pruebas
debe cubrir dos usuarios, herencia de colección, superadmin, recurso inexistente o
eliminado, revocación inmediata, transferencia y coherencia de rutas anidadas.
