<?php

declare(strict_types=1);

namespace App\Documentation\Cms;

use OpenApi\Attributes as OA;

/**
 * OpenAPI definitions for BlockType endpoints.
 *
 * @OA\Tag(name="Cms", description="Cms management")
 */
class BlockTypeEndpoints
{
    #[OA\Get(
        path: '/api/v1/cms/block-types',
        tags: ['Cms'],
        summary: 'List BlockTypes',
        responses: [
            new OA\Response(
                response: 200,
                description: 'List retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/BlockTypeResponse')
                        ),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function index(): void
    {
    }

    #[OA\Post(
        path: '/api/v1/cms/block-types',
        tags: ['Cms'],
        summary: 'Create new BlockType',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/BlockTypeCreateRequest')
        ),
        responses: [
            new OA\Response(response: 201, description: 'Created successfully'),
            new OA\Response(response: 422, description: 'Validation error')
        ]
    )]
    public function store(): void
    {
    }

    #[OA\Get(
        path: '/api/v1/cms/block-types/{id}',
        tags: ['Cms'],
        summary: 'Get BlockType by ID',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Found',
                content: new OA\JsonContent(ref: '#/components/schemas/BlockTypeResponse')
            ),
            new OA\Response(response: 404, description: 'Not found')
        ]
    )]
    public function show(): void
    {
    }

    #[OA\Put(
        path: '/api/v1/cms/block-types/{id}',
        tags: ['Cms'],
        summary: 'Update existing BlockType',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/BlockTypeUpdateRequest')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Updated successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/BlockTypeResponse')
            ),
            new OA\Response(response: 404, description: 'Not found'),
            new OA\Response(response: 422, description: 'Validation error')
        ]
    )]
    public function update(): void
    {
    }

    #[OA\Delete(
        path: '/api/v1/cms/block-types/{id}',
        tags: ['Cms'],
        summary: 'Delete BlockType by ID',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 204, description: 'Deleted successfully'),
            new OA\Response(response: 404, description: 'Not found')
        ]
    )]
    public function delete(): void
    {
    }
}
