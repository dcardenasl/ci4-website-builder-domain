<?php

declare(strict_types=1);

namespace App\Documentation\Cms;

use OpenApi\Attributes as OA;

/**
 * OpenAPI definitions for the unauthenticated entry listing.
 */
class PublicEntryEndpoints
{
    #[OA\Get(
        path: '/api/v1/public/{lang}/entries/{collectionKey}',
        tags: ['Cms'],
        summary: 'List public entries',
        parameters: [
            new OA\Parameter(name: 'lang', in: 'path', required: true, schema: new OA\Schema(type: 'string', maxLength: 10)),
            new OA\Parameter(name: 'collectionKey', in: 'path', required: true, schema: new OA\Schema(type: 'string', maxLength: 50)),
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', minimum: 1, default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100, default: 20)),
            new OA\Parameter(name: 'category', in: 'query', schema: new OA\Schema(type: 'string', maxLength: 150)),
            new OA\Parameter(name: 'tag', in: 'query', schema: new OA\Schema(type: 'string', maxLength: 100)),
            new OA\Parameter(name: 'q', in: 'query', schema: new OA\Schema(type: 'string', maxLength: 255)),
            new OA\Parameter(name: 'order_by', in: 'query', schema: new OA\Schema(type: 'string', enum: ['published_at', 'sort_order', 'created_at', 'title'])),
            new OA\Parameter(name: 'order_direction', in: 'query', schema: new OA\Schema(type: 'string', enum: ['asc', 'desc', 'ASC', 'DESC'])),
            new OA\Parameter(name: 'include', in: 'query', schema: new OA\Schema(type: 'string', enum: ['listing_content'])),
            new OA\Parameter(
                name: 'filters',
                in: 'query',
                description: 'Indexed filter rows. The field/operator matrix is closed by the PublicEntryFilter schema.',
                style: 'deepObject',
                explode: true,
                schema: new OA\Schema(
                    type: 'array',
                    maxItems: 6,
                    items: new OA\Items(ref: '#/components/schemas/PublicEntryFilter')
                )
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Public entries returned'),
            new OA\Response(response: 404, description: 'Collection or language not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function index(): void
    {
    }
}
