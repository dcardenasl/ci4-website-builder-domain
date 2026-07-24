# Content Blocks System Architecture

The Content Blocks system is a schema-driven architecture that allows developers and administrators to build and layout page layouts dynamically from the admin panel. Rather than using fixed page templates, pages are composed of an ordered list of reusable blocks.

---

## 1. Key Concepts

- **Block Type:** Defines the data structure (schema) and the visual template used to render the block. Example: `hero_slider`, `rich_text`, `accordion`.
- **Block Instance:** The actual block added to a page with specific translation values and settings.
- **Native Field Primitive:** Code-level field capability such as `text`, `textarea`, `richtext`, `image`, `file`, `url`, `number`, `boolean`, `select`, `date`, or `datetime`. These primitives are structural system capabilities and do not depend on seeded block types.
- **Schema Definition:** JSON formatted description detailing the block's fields. Divided into:
  - `fields`: Localized fields filled per language (e.g., title, description, image).
  - `config_fields`: Non-localized styling settings (e.g., CSS class, color variant).

Block types live in the database as editable configuration composed from native primitives. Seeders may install starter/demo block types, but the CMS wizard must introspect the active database block types and continue to work when no starter block type seeders have been installed.

---

## 2. Supported Field Types

The block type designer visually supports the following field types:
- **`string` / `text` / `richtext`:** Simple text inputs, textareas, and rich HTML editors.
- **`url`:** Website links with validation.
- **`integer` / `boolean`:** Numeric inputs and yes/no toggles.
- **`select`:** Preset select dropdowns.
- **`file`:** Integration with the File Manager (image/video pickers).
- **`repeater` (Repeaters):** Enables adding dynamic lists of items containing nested subfields (`item_fields`). Ideal for basic grids of cards.

For wizard rendering, aliases are normalized to native primitives. For example, `string` becomes `text`, `text` becomes `textarea`, `rich_text` becomes `richtext`, and `file` with an image accept rule becomes `image`.

---

## 3. Container-Child Relations (Parent-Child)

For complex component hierarchies (sliders, accordion groups, card columns), the page builder utilizes container blocks.

### Key Properties:
- **`is_container`:** A boolean field in the block type. If `true`, the admin dashboard exposes a hierarchical management screen (via the **"Slides"** button).
- **`allowed_children`:** Stored within the container's schema definition. An array of strings containing the keys (`block_key`) of the allowed child block types.

### Example Configuration (Database schema JSON):
```json
{
  "fields": [],
  "config_fields": {
    "css_class": { "type": "string", "label": "CSS Class" }
  },
  "allowed_children": ["accordion_item"]
}
```

### Admin Panel Workflow:
1. When viewing a page's block list, container blocks display a **"Slides"** button.
2. Clicking **"Slides"** opens a dedicated child list view where child blocks are managed and ordered, using a `parent_instance_id` reference.
3. Clicking **"Add Slide"** triggers a controller that resolves the parent's block type, queries its `allowed_children` constraint, and filters options in the block type selector to only expose matching items.

---

## 4. Database Structure

The tables involved in the CMS module are:

### `cms_content_blocks` (Block Types)
- `block_key` (VARCHAR): Unique key identifier (e.g. `accordion`).
- `name`, `description`, `category`, `icon`: Visual metadata.
- `schema_definition` (JSON): Schema fields and allowed children configurations (`allowed_children`).
- `is_container` (TINYINT): Boolean toggle allowing nested instances.

### `cms_page_blocks` (Block Instances)
- `page_id` (INT): Parent page relation.
- `block_id` (INT): Active block type reference.
- `parent_instance_id` (INT, Nullable): Hierarchy reference to the parent block instance.
- `sort_order` (INT): Display sort order weight.
- `block_config` (JSON): Flat object representing non-translated values.

### `cms_page_block_translations` (Localized Data)
- `page_block_id` (INT): Instance reference.
- `language_id` (INT): Translation locale.
- `block_data` (JSON): The actual values matching the schema's field keys (e.g. title, body text).
