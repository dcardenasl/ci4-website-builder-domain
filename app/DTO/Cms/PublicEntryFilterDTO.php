<?php

declare(strict_types=1);

namespace App\DTO\Cms;

use dcardenasl\Ci4ApiCore\Exceptions\ValidationException;
use OpenApi\Attributes as OA;

/**
 * Validated public entry filter.
 *
 * A value object, not a request DTO: it validates one filter of a larger
 * payload through `fromArray($raw, $index)` and has no `rules()` of its own.
 * `PublicEntryIndexRequestDTO` is the request DTO that composes it — which is
 * why this lives outside `DTO/Request`, where every class is expected to be a
 * `BaseRequestDTO`.
 *
 * The field/operator matrix is deliberately closed. Public callers may select
 * business fields only; they cannot provide SQL identifiers or query fragments.
 */
#[OA\Schema(
    schema: 'PublicEntryFilter',
    type: 'object',
    required: ['field', 'operator', 'value'],
    properties: [
        new OA\Property(property: 'field', type: 'string', enum: ['entry.title', 'entry.excerpt', 'entry.slug', 'entry.published_at', 'entry.created_at', 'taxonomy.categories', 'taxonomy.tags']),
        new OA\Property(property: 'operator', type: 'string', enum: ['equals', 'contains', 'before', 'after', 'in']),
        new OA\Property(property: 'value', oneOf: [
            new OA\Schema(type: 'string'),
            new OA\Schema(type: 'array', maxItems: 20, items: new OA\Items(type: 'string')),
        ]),
    ]
)]
readonly class PublicEntryFilterDTO
{
    public const MAX_FILTERS = 6;

    public const MAX_VALUES = 20;

    public const MAX_VALUE_LENGTH = 255;

    /** @var array<string, list<string>> */
    public const FIELD_OPERATORS = [
        'entry.title' => ['equals', 'contains'],
        'entry.excerpt' => ['equals', 'contains'],
        'entry.slug' => ['equals', 'contains'],
        'entry.published_at' => ['equals', 'before', 'after'],
        'entry.created_at' => ['equals', 'before', 'after'],
        'taxonomy.categories' => ['equals', 'in'],
        'taxonomy.tags' => ['equals', 'in'],
    ];

    /**
     * @param list<string> $values
     */
    public function __construct(
        public string $field,
        public string $operator,
        public array $values,
    ) {
    }

    /**
     * @param mixed $raw
     */
    public static function fromArray(mixed $raw, int $index): self
    {
        if (! is_array($raw)) {
            self::invalid($index, 'filter', (string) lang('Cms.public_filters.object'));
        }

        $field = trim((string) ($raw['field'] ?? ''));
        if (! isset(self::FIELD_OPERATORS[$field])) {
            self::invalid($index, 'field', (string) lang('Cms.public_filters.field_not_allowed'));
        }

        $operator = strtolower(trim((string) ($raw['operator'] ?? '')));
        if (! in_array($operator, self::FIELD_OPERATORS[$field], true)) {
            self::invalid($index, 'operator', (string) lang('Cms.public_filters.operator_not_allowed'));
        }

        $rawValue = $raw['value'] ?? null;
        if ($operator === 'in') {
            if (! is_array($rawValue) || ! array_is_list($rawValue) || $rawValue === []) {
                self::invalid($index, 'value', (string) lang('Cms.public_filters.in_requires_list'));
            }

            if (count($rawValue) > self::MAX_VALUES) {
                self::invalid($index, 'value', (string) lang('Cms.public_filters.in_too_many'));
            }

            $values = [];
            foreach ($rawValue as $valueIndex => $value) {
                $values[] = self::normalizeValue($value, $index, 'value.' . $valueIndex);
            }
            $values = array_values(array_unique($values));

            if ($values === []) {
                self::invalid($index, 'value', (string) lang('Cms.public_filters.in_requires_list'));
            }
        } else {
            if (is_array($rawValue) || $rawValue === null) {
                self::invalid($index, 'value', (string) lang('Cms.public_filters.scalar_required'));
            }

            $values = [self::normalizeValue($rawValue, $index, 'value')];
        }

        return new self($field, $operator, $values);
    }

    /**
     * @return array{field: string, operator: string, values: list<string>}
     */
    public function toArray(): array
    {
        return [
            'field' => $this->field,
            'operator' => $this->operator,
            'values' => $this->values,
        ];
    }

    /**
     * Stable representation for a future result-cache key.
     */
    public function canonicalValue(): string
    {
        $values = $this->values;
        sort($values, SORT_STRING);

        return json_encode([
            'field' => $this->field,
            'operator' => $this->operator,
            'values' => $values,
        ], JSON_THROW_ON_ERROR);
    }

    private static function normalizeValue(mixed $value, int $index, string $errorKey): string
    {
        if (! is_string($value) && ! is_int($value) && ! is_float($value)) {
            self::invalid($index, $errorKey, (string) lang('Cms.public_filters.value_scalar'));
        }

        $normalized = trim((string) $value);
        if ($normalized === '') {
            self::invalid($index, $errorKey, (string) lang('Cms.public_filters.value_required'));
        }

        if (mb_strlen($normalized) > self::MAX_VALUE_LENGTH) {
            self::invalid($index, $errorKey, (string) lang('Cms.public_filters.value_too_long'));
        }

        return $normalized;
    }

    private static function invalid(int $index, string $key, string $message): never
    {
        throw new ValidationException(
            lang('Api.validationFailed'),
            ['filters.' . $index . '.' . $key => $message]
        );
    }
}
