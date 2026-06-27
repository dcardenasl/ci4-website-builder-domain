# Arquitectura de Archivos

Este documento define el modelo canónico de archivos para el CMS.

## Fuente de verdad

- `file_id` identifica el archivo.
- `file_references` es el registro canónico de "dónde se usa".
- Las URLs persistidas son salida derivada, no dato canónico.
- El backend debe resolver la URL final según el contexto de consumo.

## Contrato de lectura

- Las respuestas públicas deben devolver URLs, no rutas de preview del admin.
- Si un payload contiene `file_id`, el backend resuelve la URL desde la tabla `files`.
- Si todavía existe una URL legacy de preview, el backend puede resolver el `file_id` y normalizar la salida.
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
- Los serializers de bloques deben normalizar `*_url` desde el `*_file_id` canónico.

## Sincronización de referencias

- Reconstruir `file_references` al guardar entradas, páginas y bloques.
- Borrar y reinsertar referencias del mismo recurso para no dejar filas obsoletas.
- Mantener las referencias estables al reemplazar el archivo. Cambia el archivo; no cambia el uso.

## Backfill y limpieza

- Ejecutar el backfill cuando existan URLs legacy o referencias faltantes.
- El backfill debe ser idempotente.
- Debe normalizar URLs, inferir `file_id` cuando sea posible y reconstruir referencias.

## Qué no hacer

- No persistir `/files/{id}/view` como dato canónico del CMS.
- No derivar URLs de archivos en el frontend.
- No actualizar referencias fuera de la transacción de guardado.
- No inventar una regla distinta por cada feature.

## Agregar un nuevo campo de archivo

1. Agregar un campo `file` al schema.
2. Persistir `*_file_id` como identidad.
3. Dejar que el backend derive `*_url`.
4. Registrar o reconstruir las referencias del nuevo uso.
5. Agregar un test de regresión para guardado, lectura y backfill.
