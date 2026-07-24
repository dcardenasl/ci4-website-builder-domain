<?php

declare(strict_types=1);

namespace App\Exceptions;

use dcardenasl\Ci4ApiCore\Exceptions\ValidationException;

class WizardConfigValidationException extends ValidationException
{
    public function __construct(string $message, string $field = 'wizard_config')
    {
        parent::__construct($message, [$field => $message]);
    }
}
