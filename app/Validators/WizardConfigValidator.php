<?php

declare(strict_types=1);

namespace App\Validators;

use App\Exceptions\WizardConfigValidationException;
use App\Libraries\Cms\WizardStepFieldCatalog;

class WizardConfigValidator
{
    /**
     * Validates a decoded wizard_config array against the fixed native-field
     * catalog. Passes silently when $config is null or has no `steps` key
     * (both fields are optional).
     *
     * @param array<string, mixed>|null $config
     * @throws WizardConfigValidationException
     */
    public function validate(?array $config): void
    {
        if ($config === null) {
            return;
        }

        $steps = $config['steps'] ?? null;
        if ($steps === null) {
            return;
        }

        if (! is_array($steps)) {
            throw new WizardConfigValidationException('steps must be an array');
        }

        $usedKeys = [];

        foreach ($steps as $stepIndex => $step) {
            if (! is_array($step)) {
                throw new WizardConfigValidationException("Step at index {$stepIndex} must be an object");
            }

            $stepTitle = $step['step_title'] ?? null;
            if (! is_string($stepTitle) || trim($stepTitle) === '') {
                throw new WizardConfigValidationException("Step at index {$stepIndex}: step_title is required");
            }

            $fields = $step['fields'] ?? null;
            if (! is_array($fields) || $fields === []) {
                throw new WizardConfigValidationException("Step at index {$stepIndex}: fields must be a non-empty array");
            }

            foreach ($fields as $fieldIndex => $field) {
                if (! is_array($field)) {
                    throw new WizardConfigValidationException("Step at index {$stepIndex}, field at index {$fieldIndex} must be an object");
                }

                $key = $field['key'] ?? null;
                if (! is_string($key) || $key === '') {
                    throw new WizardConfigValidationException("Step at index {$stepIndex}, field at index {$fieldIndex}: key is required");
                }

                if (! WizardStepFieldCatalog::isAllowedKey($key)) {
                    throw new WizardConfigValidationException("Field '{$key}': not part of the native field catalog");
                }

                if (in_array($key, $usedKeys, true)) {
                    throw new WizardConfigValidationException("Field '{$key}' is used in more than one step");
                }
                $usedKeys[] = $key;

                $expectedType = WizardStepFieldCatalog::expectedType($key);
                $type = $field['type'] ?? null;
                if ($type !== $expectedType) {
                    throw new WizardConfigValidationException("Field '{$key}': type must be \"{$expectedType}\"");
                }
            }
        }

        if (! in_array(WizardStepFieldCatalog::ANCHOR_KEY, $usedKeys, true)) {
            throw new WizardConfigValidationException('The "' . WizardStepFieldCatalog::ANCHOR_KEY . '" field must be present in at least one step');
        }
    }
}
