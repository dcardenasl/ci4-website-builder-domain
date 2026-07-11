# TASKS — ci4-website-builder-domain

> Fuente de verdad **local** para trabajo en este repo.
> Para tareas globales del proyecto, ver: [../TASKS.md](../TASKS.md)
> Plan detallado de Form Submissions: [../../docs/form_submissions_plan.md](../../docs/form_submissions_plan.md)
> Última actualización: 2026-06-23 (CMS-012 completado, CMS-013-016 en TASKS.md raíz)

---

## 🔴 En progreso
*(vacío)*

---

## 🟡 Próximo

*(vacío — backlog abajo)*

Las tareas están ordenadas por fases de dependencias para asegurar la integridad de la base de datos (30 tablas de `erd_cms_v4.html`) y las APIs.

---

## ✅ Completadas (2026-07-11)

- [DEEP-BLOCK-01] Unificada la fuente de verdad del catálogo de bloques (H-010). `BlockTemplateCatalog` dejó de ser un array estático duplicado y ahora proyecta `default_schema` directamente desde `cms_content_blocks` (repositorio inyectado, wired en `CmsDomainServices::blockTemplateCatalog()`); `preview_sample`/`config_sample` quedan como sugar presentacional autolimpiante (`array_intersect_key` contra los campos reales, nunca puede mentir sobre el schema). Corrige `hero_slider` (era 3 slides planos, es contenedor + hijos `slide_banner`) e `image` (era `file_id`/`url`, es campo único `image` tipo file), y de paso expone los 13 block_key que el catálogo nunca tuvo. Bonus: `image.php` preview partial en Admin leía `data['url']` en vez de `data['image_url']` (mismo bug de convención de campo file, nunca mostraba la imagen real). 6 tests de paridad nuevos + 2 tests nuevos en Admin.
- [DEEP-TRAN-01] `TranslationAuditService` dejó de resolver 20 Models vía `model()` en su propio constructor (H-011). Ahora recibe todo por DI explícita (wiring movido a `CmsDomainServices::translationAuditService()`) y las 9 auditorías de "recurso simple" (antes 9 métodos privados casi idénticos + switch de 9 casos en `auditResource()`) se colapsaron a un catálogo interno de descriptores (`buildSimpleResourceDescriptors()`) recorrido con un loop. `setting` y `block_instance` siguen separados porque su lógica es genuinamente distinta (fallback de idioma por defecto / schema-driven). Guardrail `ServiceModelDependencyConventionsTest` y `architecture-baseline.json` raíz actualizados (entrada de 21 `model_call` eliminada, confirmado en 0). Las 18 pruebas de comportamiento existentes (Unit+Feature, con DB real) pasan sin cambios — preserva la interfaz pública exacta.
- [PHPSTAN-01..09] Remediación completa de PHPStan tras ampliar `paths` a `app/DTO`, `app/Repositories`, `app/Commands`. El baseline temporal `phpstan-expanded-baseline.neon` (825 errores) se redujo a 0 y fue eliminado — `phpstan.neon` vuelve a incluir solo `phpstan-baseline.neon` (36 entradas, deuda preexistente sin tocar, fuera de este scope). Cambios:
  - Bootstrap: `EXIT_SUCCESS`/`EXIT_ERROR` stubbeados en `phpstan-bootstrap.php`.
  - `ignoreErrors` documentado y acotado a `app/DTO/*` para el mismo falso positivo de `BaseRequestDTO::map()` que en el hub.
  - **Bug real encontrado y corregido**: `BackfillCmsFileReferences` instanciaba `new FileUrlResolver($db)` pasando una conexión de DB donde el constructor espera `?HubClient` — nunca usaba `$db`.
  - **Bug funcional real encontrado y corregido**: `FormUpdateRequestDTO::toArray()` comprobaba `array_key_exists('notify_email', $data)` sobre el `$data` *local* de `toArray()` (recién construido) en vez del `$data` original de `map()`. Efecto: si el cliente enviaba `{"notify_email": null}` para limpiar el campo, la intención se perdía silenciosamente al serializar — el forms API nunca propagaba el `null`. Corregido rastreando `providedFields` desde `map()`.
  - False-safety: `?->getRowArray()`/`file()`/`json_encode()`/`strtotime()` sin chequear `false` en `SyncPermissions`, `BackfillCmsFileReferences`, `CollectionResponseDTO`.
  - `AuditRepository`: generic `@extends BaseRepository<object>` (debe coincidir con `AuditRepositoryInterface`), `@var list<AuditLogEntity>` tras `findAll()`, `array_values()` para garantizar `list` en los facets.
  - `QueueWork`: guard `method_exists($queueManager, 'getStats')` agregado junto al de `process()` ya existente (mismo patrón, PHPStan reconoce `method_exists()` como narrowing).
  - ~25 anotaciones `array<string,mixed>`/`list<...>` agregadas en DTOs de Cms/Audit.
  - Verificado: PHPStan 0 errores, CS-Fixer limpio, 220 tests unitarios + 99 tests feature en verde (incluye `PublicFormControllerTest`, que ejercita el DTO corregido).

---

---

> **Nota:** Las tareas CMS-012 a CMS-016 (Form Submissions) ahora se rastrean en [`../TASKS.md`](../TASKS.md) en la raíz del proyecto.
> Este archivo es solo para tareas locales del domain (DOM-xxx, bug fixes, optimizaciones arquitectónicas).

---

## ✅ Completadas

### CMS-010 (#9) — Redirects & Slug history
- Implementado CRUD de redirecciones manuales y automáticas (historial de slugs).
- Creado módulo `SlugRedirectRecorder` para registrar cambios de slugs históricos de páginas y entradas.
- Implementado endpoint público de resolución de redirecciones con soporte para múltiples segmentos de path.
- Agregados tests unitarios y de integración con cobertura del 100% de calidad.

### CMS-005 (#4) — Menus API
- Implementado CRUD de menús y de sus items de menú (anidados, ordenados, traducibles).
- Implementado el árbol público de menús con traducción por `Accept-Language` y `is_fallback`.
- Agregados tests de feature e integración para admin, público y `TranslationResolver`.

### CMS-006 (#5) — Block system
- Implementado CRUD de block types (`cms_content_blocks`) con seeds (`rich_text`, `image`, `cta`).
- Implementado CRUD de block instances con `block_config` / `block_data` y serialización para páginas y entradas.
- Agregados tests de integración para `BlockInstanceSerializer`, block types y consumo público de páginas/entries.

### CMS-004 (#3) — Pages API
- Implementado CRUD de páginas con persistencia de campos traducibles e historial de versiones.
- Creado módulo `SlugRouter` para enrutamiento de páginas jerárquicas multilingües.
- Agregados tests unitarios y de integración con cobertura del 100% de calidad.

### CMS-002 (#2) — Languages & Settings API
- CRUD de idiomas y settings
- Crear el deep module `TranslationResolver` con unit tests

### CMS-003 (#10) — File translations
- CRUD de traducciones de archivos (`cms_file_translations`)
- Integrar metadatos en `BlockInstanceSerializer`

### CMS-001 (#1) — Bootstrap & Schema completo
- Registrar la app CMS en el hub (`php spark apps:bootstrap cms --create-api-key`)
- Definir permisos `cms.*` en `DomainPermissions.php`
- Crear e integrar la migración única con las 30 tablas del ERD v4
- Adaptar `init.sh` para la orquestación desatendida
- Proteger endpoints `/api/v1/cms/*` y abrir `/api/v1/public/*`

### Fase 3: Composable Block System

### Fase 4: Collections & Entries
- [x] **CMS-007 (#6) — Collections API**
  - CRUD de colecciones personalizables (`collection_key`)
- [x] **CMS-008 (#7) — Entries API**
  - CRUD de entries vinculando instancias de bloques de contenido por entry
- [x] **CMS-009 (#8) — Taxonomías: Categories & Tags**
  - CRUD de categorías scoped y tags globales + pivots + filtros en listado de entries

### Fase 5: Publishing & Utilities
- [x] **CMS-011 (#11) — Scheduled publishing**
  - Queue job `ScheduledPublishingJob` para publicar páginas y entries en segundo plano

---

## ✅ Completadas

### DOM-110 — Automatización de Sync de Permisos en Desarrollo (DX)
- Modificar `app/Commands/SyncPermissions.php` para resolver automáticamente el token de administración en local usando la DB de IAM local en desarrollo.
- Implementar borrado automático de caché (`cache:clear`) al terminar la sincronización local.

### DOM-111 — Documentación de Arquitectura de Seguridad
- Agregar `docs/architecture/permissions.md` detallando el flujo de permisos cruzados y la caché de introspección.

### DOM-109 — `domain:sync-permissions`: fail-loud + HubClient role lookup fix (KICK-027)
- **Qué**: (1) `HubClient::findRoleByCode` ahora parsea `{items:[...]}` en vez de `$data[0]` — el API devuelve una colección paginada, no un array plano; (2) `SyncPermissions` ahora termina con `exit≠0` cuando `--assign-to-role` está seteado pero el rol no se encontró/enlazó (`$roleLinkFailed` flag); (3) composer.lock actualizado a ci4-api-core v0.9.3 que incluye `registerPermission(3 params)` para reenviar `applicationId` correctamente; (4) tests añadidos en `HubClientTest` y `SyncPermissionsTest` para los dos behaviors corregidos.
- **Por qué**: en el POC E2E (2026-06-03) `domain:sync-permissions --assign-to-role superadmin` reportaba éxito pero no enlazaba nada al rol: (a) `findRoleByCode` retornaba null porque intentaba `$data[0]` sobre un `{items:[...]}` paginado, y (b) la firma de 2 parámetros en la versión bloqueada de api-core descartaba el `application_id` del mirror.
- **Verificado**: PHP lint limpio, bash -n limpio, tests pasan.

### DOM-103 — `php spark domain:doctor` diagnóstico del hub
- **Qué**: se añadió `php spark domain:doctor` para auditar el enlace del domain starter con el hub. El comando reporta tres checks: `service-token`, `introspect` cuando se pasa `--token`, y `register-permission` cuando se pasa `--admin-token`. El probe de registro usa un payload inválido a propósito para mantenerse read-only y solo validar reachability/autenticación.
- **Por qué**: la tarea pedía un diagnóstico operativo que ayudara a detectar problemas de conectividad y auth sin tener que lanzar manualmente varios comandos de setup.
- **Verificado**: `vendor/bin/phpunit tests/Unit/Commands/DoctorTest.php --testdox --no-coverage` ✅ (2 tests, 17 assertions).

### DOM-105 — Strip `AuthTokenSchema` leftover (2026-05-26)
- **Qué**: se eliminó `app/Documentation/Common/AuthTokenSchema.php`, un leftover heredado del clone de `ci4-api-starter` que ya no correspondía al contrato actual del domain starter. Durante la verificación también se tipó `app/Services/Example/ItemService.php` con el genérico `ItemEntity` y se regeneró `public/swagger.json` para aceptar la salida real del generador OpenAPI.
- **Por qué**: el archivo referenciaba un schema inexistente y hacía más frágil la validación OpenAPI del repo sin aportar valor funcional. El ajuste de generics cerró un drift de PHPStan que apareció al correr `composer quality`.
- **Verificado**: `composer quality` limpio en el repo (PHPStan, CS-Fixer, OpenAPI y PHPUnit).

### DOM-108 — Onboarding desatendido y vinculación de roles (2026-05-25)
- **Qué**: `init.sh` ahora acepta `--assign-to-role=ID|code` y lo pasa a `domain:sync-permissions`. `HubClient` captura `ValidationException` para tratar 422 como éxito idempotente. `init.sh` corre `php spark core:install` automáticamente.
- **Por qué**: (Bulletproof V2) Permitir despliegues 100% automáticos desde el orquestador, vinculando nuevos permisos al rol `superadmin` sin intervención manual. Garantizar que el runtime del core esté listo tras el bootstrap.
- **Verificado**: `php -l` limpio. Scripts probados en flujo de kickstart.

### DOM-107 — Patrón de aggregate extension documentado
- **Qué**: `docs/architecture/EXTENSION_GUIDE.{md,es.md}` ahora documenta cuándo `make:crud` deja de alcanzar y cómo evolucionar el módulo generado hacia un aggregate con custom actions, nested resources, relation sync y response enrichment. `README.md` y `docs/README.md` enlazan explícitamente ese patrón.
- **Por qué**: la auditoría del bootstrap `ci4-catalog` mostró que el problema no era solo generar menos código, sino no tener una guía canónica para el salto desde CRUD plano a aggregate real.
- **Verificado**: documentación enlazada desde los entry points principales del repo (`README.md`, `docs/README.md`) y alineada con el playbook de scaffolding existente.

### DOM-106 — Paridad `boolean_like` con el scaffolder
- **Qué**: `App\Validations\Rules\CustomRules` ahora implementa `boolean_like()` con el mismo contrato esperado por `ci4-api-scaffolding`: acepta bools, `0/1`, y strings `true/false/yes/no/on/off` de forma case-insensitive. Se añadieron los strings de validación en `app/Language/en/Validation.php` y `app/Language/es/Validation.php`.
- **Por qué**: el scaffolder emite `boolean_like` para fields `bool`, pero `ci4-website-builder` no exponía esa regla. Eso rompía CRUDs generados con booleanos y obligaba a parchear DTOs/modelos a mano.
- **Verificado**: `vendor/bin/phpunit tests/Unit/Validations/CustomRulesTest.php --configuration=phpunit.xml --no-coverage --testdox` ✅ (10 tests, 28 assertions).

### BFF-107 — Refactor `HubClient` sobre `AbstractServiceClient`
- **Qué**: `app/Libraries/Hub/HubClient.php` pasó de 220 a 155 líneas extendiendo `dcardenasl\Ci4ApiCore\Http\Client\AbstractServiceClient`. Paths del hub movidos a `Config\Hub::$introspectPath/$serviceTokenPath/$permissionsPath`. `RuntimeException` reemplazado por `ServiceUnavailableException`/`AuthenticationException`/`AuthorizationException` canónicas. `registerPermission()` ahora trata 422 igual que 409 como duplicado idempotente. Heredada gratis: propagación de `X-Request-Id`, retry 1× en 5xx/network, allow-list de headers en `forward()`.
- **Por qué**: eliminar drift entre los dos `HubClient.php` (BFF-102 hizo el mismo refactor en el BFF). Cualquier ajuste futuro a timeout/retry/headers se hace una vez, en el core.
- **Verificado**: `DomainAuthFilter` consume `HubClient::introspect()` que mantuvo su firma (devuelve `IntrospectResult`) — cero cambios necesarios en el filter. `composer quality` limpio en domain (PHPStan L8 + CS-Fixer + 145 tests / 353 assertions). 10 tests nuevos en `HubClientTest` (cache hit, refresh, 5xx con retry, introspect downgrade, registerPermission idempotente, 401/403 → excepciones canónicas).
- **Cross-repo**: ver `../TASKS.md` milestone "ci4-bff-starter v1.1".

### DOM-102 — ADR-001: Hub-Domain Split Architecture (2026-05-26)
- **Qué**: Documentación centralizada en `TASKS.md` y `README.md` sobre la delegación de autenticación, propiedad de permisos y la prohibición explícita de tablas de usuarios en dominios.
- **Por qué**: Establecer la arquitectura canónica para evitar deuda técnica al escalar dominios.
- **Verificado**: Arquitectura documentada en "Contratos de arquitectura".

### DOM-101 — Suite de Smoke tests (2026-05-26)
- **Qué**: Implementación de tests críticos (`DomainAuthFilterTest`, `HubClientTest`, `CreateItemTest`) garantizando la integridad del flujo principal.
- **Por qué**: Asegurar que la delegación de auth y la comunicación con el hub son robustas antes de despliegue.
- **Verificado**: Suite de 145 tests / 353 assertions activa y pasando en `composer quality`.

---

## ⚪ Backlog

*(vacío)*

## 🏗️ Contratos de arquitectura

- **DTO-First:** todo Controller in/out usa DTOs. Request DTOs extienden `BaseRequestDTO`. Nunca arrays raw.
- **Services puros:** no conocen HTTP. Reciben DTOs, devuelven DTOs o lanzan excepciones de dominio.
- **Controllers delgados:** usar `ApiController::handleRequest()`. Sin lógica de negocio.
- **Separador de permisos:** punto `.` (NO `:`).
- **Hub delegation:** nunca validar JWTs localmente. Siempre `HubClient::introspect()`.
- **No tabla users:** si estás agregando una migración de usuarios, para — esos datos viven en el hub.
- **Rutas por dominio:** `app/Config/Routes/v1/<dominio>.php`.
- **Tests:** todo endpoint nuevo necesita al menos un Feature test (o waiver explícito en TASKS.md).
- **`composer cs-fix` antes de commitear.** No bypasear el pre-commit hook con `--no-verify`.

### 🚧 Technical Debt (Orchestration)
- [x] **Clean .env Management**: Migrate init.sh from appending to .env to using bootstrap_env.php to prevent duplicate keys. ✅ (Verificado en Bulletproof V2)
- [x] **Permission Assignment**: Add --assign-to-role=superadmin option to domain:sync-permissions to automate linking new permissions. ✅ 2026-05-25
