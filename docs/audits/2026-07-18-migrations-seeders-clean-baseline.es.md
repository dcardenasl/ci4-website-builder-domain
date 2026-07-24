# Baseline limpio de migraciones y seeders

## Regla final

Las migraciones crean exclusivamente la estructura final del proyecto. Los seeders crean exclusivamente el contenido inicial de la página demo. No existen migraciones de upgrade, ALTER, backfill, normalización, reparación ni datos insertados por migración.

## Diseño resultante

- 26 migraciones superiores, todas nombradas `Create*`.
- Cada `up()` crea su tabla o estructura final y cada `down()` la elimina.
- 26 seeders superiores; `SiteBootstrapSeeder` orquesta el sitio inicial completo e idempotente.
- Settings, contenido ES/EN, menús, páginas legales, colecciones, entradas, formularios y bloques demo se crean en seeders.
- Referencias multimedia usan exactamente `{source_kind,file_id,url}`; assets compartidos están en `block_config` y texto localizado en `block_data`.
- Se eliminaron migraciones, comandos, seeders, helpers y pruebas cuya responsabilidad era reparar formatos históricos.
- No se requiere ningún comando de reparación después de `migrate` + `db:seed SiteBootstrapSeeder`.

## Garantías

`CleanDatabaseBootstrapConventionsTest` impide reintroducir migraciones que no sean de creación, SQL DML/ALTER, seeders de reparación o claves multimedia retiradas. `SiteBootstrapSchemaAlignmentTest` ejecuta el bootstrap completo y valida schemas, tipos, campos requeridos/localizados, referencias multimedia y ausencia de tokens demo sin resolver.

## Verificación

- Fresh install real sobre la base principal: 26 migraciones y bootstrap completo aprobados.
- Domain: 430 pruebas y 5.509 aserciones.
- Alineación schema/seeds: 3.704 aserciones.
- Ningún parche o paso manual es parte del arranque.

