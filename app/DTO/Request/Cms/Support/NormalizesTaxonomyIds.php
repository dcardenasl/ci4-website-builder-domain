<?php

declare(strict_types=1);

namespace App\DTO\Request\Cms\Support;

use dcardenasl\Ci4ApiCore\Exceptions\ValidationException;

trait NormalizesTaxonomyIds
{
    /**
     * @param mixed $rawIds
     * @return list<int>
     */
    private function normalizeTaxonomyIds(
        mixed $rawIds,
        string $field,
        string $invalidMessage,
        string $notFoundMessage,
    ): array {
        $ids = is_array($rawIds) ? $rawIds : [];

        /** @var list<int> $normalized */
        $normalized = [];
        foreach ($ids as $id) {
            $isValidId = is_int($id) || (is_string($id) && ctype_digit($id));
            if (! $isValidId || (int) $id <= 0) {
                throw new ValidationException(
                    lang($invalidMessage),
                    [$field => lang($notFoundMessage)]
                );
            }
            $normalized[] = (int) $id;
        }

        return array_values(array_unique($normalized));
    }
}
