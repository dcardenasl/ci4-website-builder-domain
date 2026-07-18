# Arquitectura de Archivos

Este documento define el modelo canónico de archivos para el CMS.

## Fuente de verdad

- `media_reference` es el único primitivo de schema para campos multimedia.
- Su forma persistida es `{source_kind, file_id, url}`.
- `file_id` identifica un archivo del Hub; las URLs externas tienen `file_id` nulo.
- `cms_file_references` es el registro canónico de "dónde se usa" propiedad de Domain.
- La base de Domain nunca posee una tabla `files` ni crea una FK hacia datos del Hub.
- Las URLs persistidas son salida derivada, no dato canónico.
- El backend debe resolver la URL final según el contexto de consumo.

## Contrato de lectura

- Las respuestas públicas deben devolver URLs, no rutas de preview del admin.
- Si un payload contiene `file_id`, el backend resuelve la URL mediante el Hub.
- El frontend nunca debe inventar rutas de archivo.

## Contrato de escritura

- Cada write path del CMS que asocie un archivo debe registrar referencias dentro de la misma transacción.
- Los resource types canónicos son:
  - `entry`
  - `page`
  - `block_instance`
- El `role` canónico debe describir el uso semántico y, si aplica, idioma o ruta de campo.
- La `label` debe ser legible para admin.

## Resolución de URLs

- El resolver debe preferir variantes de imagen cuando existan.
- El consumo público debe usar la URL resuelta por backend, no la preview cruda del admin.
- Los serializers de bloques resuelven en batch las URLs de media references desde el file ID canónico.

## Sincronización de referencias

- Reconstruir `cms_file_references` dentro de la misma transacción al guardar entradas, páginas, bloques o schemas de bloque con media.
- Borrar y reinsertar referencias del mismo recurso para no dejar filas obsoletas.
- Mantener las referencias estables al reemplazar el archivo. Cambia el archivo; no cambia el uso.

## Consistencia desde el inicio

- La migración base crea `cms_file_references` junto con el resto de la estructura de bloques.
- Los seeders escriben exclusivamente payloads canónicos de media.
- `SiteBootstrapSeeder` reconstruye el registro después de las escrituras directas de seeders.
- No existen migraciones ni comandos de conversión de formatos anteriores.

## Qué no hacer

- No persistir `/files/{id}/view` como dato canónico del CMS.
- No derivar URLs de archivos en el frontend.
- No actualizar referencias fuera de la transacción de guardado.
- No inventar una regla distinta por cada feature.

## Agregar un nuevo campo de archivo

1. Agregar un campo `media_reference` al schema y declarar su valor `accept`.
2. Persistir el valor anidado `{source_kind, file_id, url}`.
3. Dejar que el backend resuelva la URL final en batch.
4. Registrar o reconstruir `cms_file_references` para el nuevo uso.
5. Agregar un test de regresión para guardado, lectura y sincronización de referencias.
