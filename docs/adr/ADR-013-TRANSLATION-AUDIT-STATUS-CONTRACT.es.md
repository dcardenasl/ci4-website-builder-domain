# ADR-013: Contrato de Estados para Auditoría de Traducciones

## Estado
Aceptado

## Contexto

El CMS sirve contenido público multilenguaje. La auditoría de traducciones necesita distinguir entre una traducción realmente ausente y un contenido parcial o inconsistente, para que el editor corrija primero el problema correcto.

## Decisión

1. La auditoría reporta tres estados:
- `missing`: no existe la fila de traducción para el idioma activo.
- `incomplete`: la fila de traducción existe, pero uno o más campos públicos requeridos están vacíos.
- `mismatch`: la fila de traducción existe, todos los campos requeridos están presentes, pero un campo público opcional tiene valor en al menos un idioma y está vacío en otro.
2. Solo los campos requeridos pueden disparar `incomplete` por sí solos.
3. Los campos opcionales no generan aviso cuando están vacíos en todos los idiomas.
4. Los campos opcionales sí generan `mismatch` cuando existe asimetría entre idiomas.
5. Los campos estructurales o internos quedan excluidos salvo que el schema los marque como auditable/público.
6. El mismo contrato aplica a páginas, menús, elementos de menú, colecciones, categorías, etiquetas, entradas, formularios, campos de formulario, configuraciones y bloques.

## Consecuencias

### Positivas
- El editor ve el problema real en vez de un aviso genérico de traducción faltante.
- La auditoría del admin sirve tanto para la creación inicial como para detectar desalineaciones posteriores.
- La auditoría de bloques se mantiene guiada por el schema y evita falsos positivos en configuración interna.

### Trade-offs
- La auditoría queda más estricta que antes y puede mostrar más hallazgos en contenido ya existente.
- Los schemas de bloques deben mantener bien sus metadatos de campos requeridos/públicos para que la auditoría sea confiable.
