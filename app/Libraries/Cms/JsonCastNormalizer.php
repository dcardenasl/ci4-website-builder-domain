<?php

declare(strict_types=1);

namespace App\Libraries\Cms;

/**
 * Normalizes a value that may arrive in any of the three shapes a `json`-cast
 * Entity field can take, into a plain (recursively-array) array:
 *   - a raw JSON string (e.g. hand-built payloads, `asArray()` query results)
 *   - a `stdClass` (CodeIgniter's `json` Entity cast decodes to stdClass by
 *     default — at every nesting level, not just the top one)
 *   - an already-decoded array
 *
 * Before this existed, three call sites each hand-rolled a slightly different
 * version of this same normalization (BlockTemplateCatalog::normalizeSchema(),
 * BlockTypeResponseDTO::fromArray(), WizardConfigService::decodeJson()) — one
 * of them (WizardConfigService's first draft) got it wrong: a shallow
 * `(array) $stdClass` cast only converts the top level, leaving nested object
 * values (e.g. `wizard_config->fields->heading`) as stdClass, which then fail
 * `is_array()` checks downstream and get silently treated as empty. Found via
 * a characterization test during the 2026-07-21 Controller→Model audit
 * (DOM-122) — see TASKS.md.
 */
final class JsonCastNormalizer
{
    /**
     * @return array<string, mixed>
     */
    public static function toArray(mixed $raw): array
    {
        if (is_array($raw)) {
            return $raw;
        }

        if (is_string($raw)) {
            $decoded = json_decode($raw, true);

            return is_array($decoded) ? $decoded : [];
        }

        if (is_object($raw)) {
            // Round-trip through JSON rather than a shallow (array) cast so
            // every nesting level becomes a real array, not just the top one.
            $decoded = json_decode((string) json_encode($raw), true);

            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }
}
