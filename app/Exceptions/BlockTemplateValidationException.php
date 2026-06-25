<?php

declare(strict_types=1);

namespace App\Exceptions;

use dcardenasl\Ci4ApiCore\Exceptions\ValidationException;

class BlockTemplateValidationException extends ValidationException
{
    public function __construct(string $message, string $field = 'block_template')
    {
        parent::__construct($message, [$field => $message]);
    }
}
