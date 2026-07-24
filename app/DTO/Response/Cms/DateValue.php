<?php

declare(strict_types=1);

namespace App\DTO\Response\Cms;

use DateTimeInterface;

final class DateValue
{
    public static function toString(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        return null;
    }
}
