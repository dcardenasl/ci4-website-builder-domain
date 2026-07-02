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
            'use_preset' => 1,
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
}
