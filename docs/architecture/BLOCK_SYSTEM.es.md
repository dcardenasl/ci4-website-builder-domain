# Arquitectura del Sistema de Bloques de Contenido (Content Blocks)

El sistema de bloques de contenido es una arquitectura orientada a esquemas (schema-driven) que permite estructurar y diseñar páginas dinámicamente desde el panel de administración. A diferencia de las plantillas fijas, las páginas se componen de una lista ordenada de bloques reutilizables.

---

## 1. Conceptos Clave

- **Tipo de Bloque (Block Type):** Define la estructura de datos (esquema) y la plantilla visual que renderizará el bloque. Ejemplo: `hero_slider`, `rich_text`, `accordion`.
- **Instancia de Bloque (Block Instance):** El bloque real insertado en una página con contenidos específicos e idiomas traducidos.
- **Primitivo Nativo de Campo:** Capacidad estructural definida en código, por ejemplo `text`, `textarea`, `richtext`, `image`, `file`, `url`, `number`, `boolean`, `select`, `date` o `datetime`. Estos primitivos no dependen de tipos de bloque instalados por seeders.
- **Esquema de Campos (Schema Definition):** Definición en formato JSON que especifica qué campos tiene el bloque. Se divide en:
  - `fields`: Campos traducibles por idioma (e.g., título, descripción, imagen).
  - `config_fields`: Campos estructurales de diseño (e.g., clase CSS, variante de color).

Los tipos de bloque viven en la base de datos como configuración editable compuesta por primitivos nativos. Los seeders pueden instalar bloques de ejemplo para el starter, pero el wizard del CMS debe inspeccionar los tipos de bloque activos y seguir funcionando aunque no se hayan instalado esos seeders.

---

## 2. Tipos de Campos Soportados

El diseñador de tipos de bloques soporta visualmente los siguientes tipos:
- **`string` / `text` / `richtext`:** Inputs de texto simple, párrafos y HTML enriquecido.
- **`url`:** Enlaces de internet con validación.
- **`integer` / `boolean`:** Números enteros y toggles sí/no.
- **`select`:** Lista de opciones predefinidas.
- **`file`:** Vinculación directa con el gestor de archivos (File Manager) para imágenes o videos.
- **`repeater` (Repetidores):** Permite añadir una lista de ítems dinámicos con subcampos personalizados (`item_fields`). Ideal para grillas de tarjetas simples.

Para renderizar el wizard, los aliases se normalizan a primitivos nativos. Por ejemplo, `string` pasa a `text`, `text` pasa a `textarea`, `rich_text` pasa a `richtext`, y `file` con regla de aceptación de imagen pasa a `image`.

---

## 3. Relaciones Contenedor-Hijo (Parent-Child)

Para estructuras más complejas (carruseles, acordeones, grillas complejas), el sistema utiliza bloques contenedores.

### Propiedades Clave:
- **`is_container`:** Un valor booleano en el tipo de bloque. Si es `true`, el admin renderiza un panel de gestión jerárquico secundario (botón **"Slides"**).
- **`allowed_children`:** Declarado dentro de la definición del esquema del bloque contenedor. Es un array de strings que contiene las claves (`block_key`) de los bloques permitidos como hijos.

### Ejemplo de Configuración en Base de Datos:
```json
{
  "fields": [],
  "config_fields": {
    "css_class": { "type": "string", "label": "Clase CSS" }
  },
  "allowed_children": ["accordion_item"]
}
```

### Flujo de Trabajo en el Panel de Administración:
1. Al listar los bloques de una página, los bloques marcados con `is_container` muestran un botón **"Slides"**.
2. Al hacer clic en **"Slides"**, se abre un sublistado donde se administran y reordenan los bloques hijos vinculados mediante el campo `parent_instance_id`.
3. Al hacer clic en **"Agregar Diapositiva"**, el controlador del panel detecta el tipo de bloque del contenedor padre, consulta sus `allowed_children` y filtra las opciones de creación para que el usuario solo elija bloques autorizados (e.g., solo añadir `accordion_item` dentro de `accordion`).

---

## 4. Estructura de la Base de Datos

Las tablas involucradas en el módulo CMS son:

### `cms_content_blocks` (Tipos de Bloques)
- `block_key` (VARCHAR): Clave única del bloque (ej. `accordion`).
- `name`, `description`, `category`, `icon`: Metadatos visuales.
- `schema_definition` (JSON): El esquema de campos y vinculación de hijos (`allowed_children`).
- `is_container` (TINYINT): Indica si acepta bloques hijos.

### `cms_page_blocks` (Instancias de Bloques)
- `page_id` (INT): Relación con la página propietaria.
- `block_id` (INT): Relación con el tipo de bloque.
- `parent_instance_id` (INT, Nullable): Relación jerárquica con el bloque contenedor padre.
- `sort_order` (INT): Orden de visualización en su nivel jerárquico.
- `block_config` (JSON): Valores no traducibles de configuración.

### `cms_page_block_translations` (Contenido Traducido)
- `page_block_id` (INT): Relación con la instancia.
- `language_id` (INT): Idioma de la traducción.
- `block_data` (JSON): Contenido real de los campos definidos en el esquema (pregunta, respuesta, etc.).
