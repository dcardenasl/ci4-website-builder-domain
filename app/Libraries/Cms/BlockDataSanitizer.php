<?php

declare(strict_types=1);

namespace App\Libraries\Cms;

/**
 * Purifies the HTML a block carries before it is stored or rendered.
 *
 * Every string that looks like markup is cleaned, wherever it sits in the tree.
 * That is deliberately broader than sanitizing only the fields a schema calls
 * rich text: this app has block data whose shape the schema does not fully
 * describe, and unescaped output is the default in the public views.
 *
 * Extracted so the persisting writer and the editor preview projector share one
 * implementation. A preview exists to show what saving would produce, so the two
 * must never clean differently.
 */
final class BlockDataSanitizer
{
    /**
     * @param array<mixed> $data
     * @return array<mixed>
     */
    public static function clean(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_string($value) && str_contains($value, '<')) {
                $data[$key] = HtmlSanitizer::clean($value);
            } elseif (is_array($value)) {
                $data[$key] = self::clean($value);
            }
        }

        return $data;
    }
}
