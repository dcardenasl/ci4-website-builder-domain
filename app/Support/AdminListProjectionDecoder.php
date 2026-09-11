<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Decodes the compatibility transport used by CMS list projections.
 *
 * Older production database engines do not provide JSON_ARRAYAGG. The SQL
 * read models therefore aggregate hex-encoded fields with GROUP_CONCAT. This
 * class performs transport decoding only; filtering, joins, ordering and
 * grouping remain database responsibilities. Malformed trailing records are
 * ignored at this boundary instead of turning a valid list response into a 500.
 */
final class AdminListProjectionDecoder
{
    /**
     * @param list<string> $fields
     * @return list<array<string, mixed>>
     */
    public static function translations(mixed $encoded, array $fields): array
    {
        if (! is_string($encoded) || $encoded === '' || $fields === []) {
            return [];
        }

        $translations = [];
        $expectedParts = count($fields) + 1;

        foreach (explode('|', $encoded) as $serialized) {
            $parts = explode(':', $serialized);
            if (count($parts) !== $expectedParts || ! ctype_digit($parts[0])) {
                continue;
            }

            $row = ['language_id' => (int) $parts[0]];
            $valid = true;

            foreach ($fields as $offset => $field) {
                $value = self::decodeHex($parts[$offset + 1]);
                if ($value === null) {
                    $valid = false;
                    break;
                }

                $row[$field] = $value === '' ? null : $value;
            }

            if ($valid) {
                $translations[] = $row;
            }
        }

        return $translations;
    }

    private static function decodeHex(string $encoded): ?string
    {
        if ($encoded === '') {
            return '';
        }

        if (strlen($encoded) % 2 !== 0 || ! ctype_xdigit($encoded)) {
            return null;
        }

        $decoded = hex2bin($encoded);

        return $decoded === false ? null : $decoded;
    }
}
