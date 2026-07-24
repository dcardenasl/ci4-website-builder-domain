<?php

declare(strict_types=1);

namespace App\Libraries\Cms;

use CodeIgniter\Entity\Entity;

/**
 * Normalizes a CI4 model result set (a mix of Entity objects and/or plain
 * arrays, as returned by findAll()/where()->findAll() depending on the
 * model's $returnType) into a plain list of arrays. Shared by every service
 * that hydrates translation rows before feeding them into a response DTO.
 */
final class ModelResultNormalizer
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function toArrayList(mixed $rows): array
    {
        if (! is_array($rows)) {
            return [];
        }

        $normalized = [];
        foreach ($rows as $row) {
            if ($row instanceof Entity) {
                $normalized[] = $row->toArray();
                continue;
            }

            if (is_array($row)) {
                $normalized[] = $row;
            }
        }

        return $normalized;
    }
}
