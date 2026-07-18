# Clean migration and seeder baseline

## Final rule

Migrations exclusively create the project's final structure. Seeders exclusively create initial demo-site content. There are no upgrade, ALTER, backfill, normalization, repair, or data-inserting migrations.

## Resulting design

- 26 top-level migrations, all named `Create*`.
- Each `up()` creates its final table/structure and each `down()` drops it.
- 26 top-level seeders; `SiteBootstrapSeeder` orchestrates the complete idempotent initial site.
- Settings, ES/EN content, menus, legal pages, collections, entries, forms, and demo blocks are created by seeders.
- Media references use exactly `{source_kind,file_id,url}`; shared assets live in `block_config` and localized text in `block_data`.
- Migrations, commands, seeders, helpers, and tests whose responsibility was repairing historical formats were removed.
- No repair command is required after `migrate` + `db:seed SiteBootstrapSeeder`.

## Guarantees

`CleanDatabaseBootstrapConventionsTest` prevents reintroducing non-creation migrations, DML/ALTER SQL, repair seeders, or retired media keys. `SiteBootstrapSchemaAlignmentTest` runs the full bootstrap and validates schemas, types, required/localized fields, media references, and the absence of unresolved demo tokens.

## Verification

- Real fresh install on the main database: all 26 migrations and the full bootstrap passed.
- Domain: 430 tests and 5,509 assertions.
- Schema/seed alignment: 3,704 assertions.
- No patch or manual step is part of startup.

