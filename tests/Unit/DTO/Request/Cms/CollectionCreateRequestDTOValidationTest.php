<?php

declare(strict_types=1);

namespace Tests\Unit\DTO\Request\Cms;

use App\DTO\Request\Cms\CollectionCreateRequestDTO;
use CodeIgniter\Config\Services;
use CodeIgniter\Test\CIUnitTestCase;
use dcardenasl\Ci4ApiCore\Exceptions\ValidationException;

/**
 * @internal
 */
final class CollectionCreateRequestDTOValidationTest extends CIUnitTestCase
{
    public function testWizardPayloadIsAcceptedByCollectionCreateDto(): void
    {
        $payload = [
            'collection_type' => 'blog',
            'collection_key' => 'blog-qa-payload',
            'sort_order' => 0,
            'translations' => [
                [
                    'language_id' => 1,
                    'slug' => 'blog-qa-payload',
                    'name' => 'Blog QA Payload',
                    'description' => '',
                ],
            ],
        ];

        try {
            new CollectionCreateRequestDTO($payload, Services::validation());
            $this->assertTrue(true);
        } catch (ValidationException $e) {
            $this->fail($e->getMessage() . ' | ' . json_encode($e->getErrors(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }
    }

    public function testValidWizardConfigStepsIsAcceptedByCollectionCreateDto(): void
    {
        $payload = [
            'collection_type' => 'blog',
            'collection_key' => 'blog-qa-wizard-config',
            'sort_order' => 0,
            'wizard_config' => [
                'type' => 'blog',
                'steps' => [
                    ['step_title' => 'Título y resumen', 'fields' => [
                        ['key' => 'title', 'label' => 'Título', 'type' => 'text', 'required' => true],
                        ['key' => 'excerpt', 'label' => 'Resumen', 'type' => 'textarea', 'required' => false],
                    ]],
                ],
            ],
            'translations' => [
                [
                    'language_id' => 1,
                    'slug' => 'blog-qa-wizard-config',
                    'name' => 'Blog QA Wizard Config',
                    'description' => '',
                ],
            ],
        ];

        try {
            new CollectionCreateRequestDTO($payload, Services::validation());
            $this->assertTrue(true);
        } catch (ValidationException $e) {
            $this->fail($e->getMessage() . ' | ' . json_encode($e->getErrors(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }
    }

    public function testInvalidWizardConfigFieldKeyIsRejectedByCollectionCreateDto(): void
    {
        $payload = [
            'collection_type' => 'blog',
            'collection_key' => 'blog-qa-wizard-config-invalid',
            'sort_order' => 0,
            'wizard_config' => [
                'type' => 'blog',
                'steps' => [
                    ['step_title' => 'Título', 'fields' => [
                        ['key' => 'title', 'label' => 'Título', 'type' => 'text', 'required' => true],
                        ['key' => 'custom_field', 'label' => 'Campo custom', 'type' => 'text', 'required' => false],
                    ]],
                ],
            ],
            'translations' => [
                [
                    'language_id' => 1,
                    'slug' => 'blog-qa-wizard-config-invalid',
                    'name' => 'Blog QA Wizard Config Invalid',
                    'description' => '',
                ],
            ],
        ];

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('not part of the native field catalog');

        new CollectionCreateRequestDTO($payload, Services::validation());
    }
}
