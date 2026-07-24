<?php

declare(strict_types=1);

namespace App\Libraries\Cms;

/**
 * Decodes the `option_labels` JSON map stored on a form field translation row
 * (value => localized label). Shared by the admin field CRUD (FormFieldService)
 * and the public form definition assembler (FormPublicDefinitionAssembler) so
 * both read the exact same stored shape.
 */
final class FormOptionLabelsCodec
{
    /**
     * @return array<string, string>
     */
    public static function decode(mixed $raw): array
    {
        if (is_array($raw)) {
            return $raw;
        }

        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);

            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }
}
