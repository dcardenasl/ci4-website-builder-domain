<?php

declare(strict_types=1);

namespace Tests\Unit\Validators;

use App\Exceptions\WizardConfigValidationException;
use App\Validators\WizardConfigValidator;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class WizardConfigValidatorTest extends CIUnitTestCase
{
    private WizardConfigValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new WizardConfigValidator();
    }

    public function testValidateNullPassesSilently(): void
    {
        $this->validator->validate(null);
        $this->assertTrue(true); // Passes without exception
    }

    public function testValidateConfigWithoutStepsKeyPassesSilently(): void
    {
        $this->validator->validate(['type' => 'other']);
        $this->assertTrue(true); // Passes without exception
    }

    public function testValidateValidConfigPasses(): void
    {
        $config = [
            'type' => 'news',
            'steps' => [
                [
                    'step_title' => 'Titular y resumen',
                    'step_hint' => 'Título visible y una breve bajada',
                    'fields' => [
                        ['key' => 'title', 'label' => 'Titular', 'type' => 'text', 'required' => true],
                        ['key' => 'excerpt', 'label' => 'Resumen', 'type' => 'textarea', 'required' => false],
                    ],
                ],
                [
                    'step_title' => 'Imagen destacada',
                    'fields' => [
                        ['key' => 'featured_image', 'label' => 'Imagen destacada', 'type' => 'image', 'required' => false],
                    ],
                ],
            ],
        ];

        $this->validator->validate($config);
        $this->assertTrue(true); // Passes without exception
    }

    public function testValidateStepsNotArrayThrowsException(): void
    {
        $this->expectException(WizardConfigValidationException::class);
        $this->expectExceptionMessage('steps must be an array');

        $this->validator->validate(['steps' => 'not-an-array']);
    }

    public function testValidateMissingStepTitleThrowsException(): void
    {
        $config = ['steps' => [
            ['fields' => [['key' => 'title', 'type' => 'text']]],
        ]];

        $this->expectException(WizardConfigValidationException::class);
        $this->expectExceptionMessage('step_title is required');

        $this->validator->validate($config);
    }

    public function testValidateEmptyFieldsThrowsException(): void
    {
        $config = ['steps' => [
            ['step_title' => 'Título', 'fields' => []],
        ]];

        $this->expectException(WizardConfigValidationException::class);
        $this->expectExceptionMessage('fields must be a non-empty array');

        $this->validator->validate($config);
    }

    public function testValidateFieldKeyOutsideCatalogThrowsException(): void
    {
        $config = ['steps' => [
            ['step_title' => 'Título', 'fields' => [
                ['key' => 'title', 'type' => 'text'],
                ['key' => 'custom_field', 'type' => 'text'],
            ]],
        ]];

        $this->expectException(WizardConfigValidationException::class);
        $this->expectExceptionMessage("not part of the native field catalog");

        $this->validator->validate($config);
    }

    public function testValidateFieldWithWrongTypeThrowsException(): void
    {
        $config = ['steps' => [
            ['step_title' => 'Título', 'fields' => [
                ['key' => 'title', 'type' => 'text'],
                ['key' => 'excerpt', 'type' => 'text'],
            ]],
        ]];

        $this->expectException(WizardConfigValidationException::class);
        $this->expectExceptionMessage('type must be "textarea"');

        $this->validator->validate($config);
    }

    public function testValidateDuplicateFieldKeyAcrossStepsThrowsException(): void
    {
        $config = ['steps' => [
            ['step_title' => 'Paso 1', 'fields' => [
                ['key' => 'title', 'type' => 'text'],
            ]],
            ['step_title' => 'Paso 2', 'fields' => [
                ['key' => 'title', 'type' => 'text'],
            ]],
        ]];

        $this->expectException(WizardConfigValidationException::class);
        $this->expectExceptionMessage("is used in more than one step");

        $this->validator->validate($config);
    }

    public function testValidateMissingTitleAnchorThrowsException(): void
    {
        $config = ['steps' => [
            ['step_title' => 'Resumen', 'fields' => [
                ['key' => 'excerpt', 'type' => 'textarea'],
            ]],
        ]];

        $this->expectException(WizardConfigValidationException::class);
        $this->expectExceptionMessage('"title" field must be present');

        $this->validator->validate($config);
    }
}
