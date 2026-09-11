<?php

declare(strict_types=1);

namespace App\DTO\Editor;

use Config\Editor;
use dcardenasl\Ci4ApiCore\Dto\DataTransferObjectInterface;
use dcardenasl\Ci4ApiCore\Exceptions\ValidationException;

/**
 * An unsaved draft submitted for rendering. Shape only — the projector applies
 * the schema rules, because a signed preview authorizes the owner, not the content.
 */
final readonly class EditorPreviewRequestDTO implements DataTransferObjectInterface
{
    public const SCOPE_DOCUMENT = 'document';
    public const SCOPE_BLOCK = 'block';

    /** @param list<array<string, mixed>> $blocks */
    private function __construct(
        public string $lang,
        public string $scopeType,
        public ?string $scopeRef,
        public array $blocks,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data, Editor $limits): self
    {
        $lang = $data['lang'] ?? null;
        $blocks = $data['blocks'] ?? null;
        $scope = $data['scope'] ?? ['type' => self::SCOPE_DOCUMENT];

        if (! is_string($lang) || preg_match('/^[a-z]{2}(?:-[A-Za-z0-9]{2,8})?$/D', $lang) !== 1
            || ! is_array($blocks) || ! array_is_list($blocks) || count($blocks) > $limits->maxBlocks
            || ! is_array($scope) || array_is_list($scope)
            || array_diff(array_keys($data), ['lang', 'blocks', 'scope']) !== []) {
            throw new ValidationException(lang('Editor.invalidPayload'));
        }

        $type = $scope['type'] ?? null;
        if (! in_array($type, [self::SCOPE_DOCUMENT, self::SCOPE_BLOCK], true)
            || array_diff(array_keys($scope), ['type', 'ref']) !== []) {
            throw new ValidationException(lang('Editor.invalidPayload'));
        }

        $ref = $scope['ref'] ?? null;
        if ($type === self::SCOPE_BLOCK && ! is_string($ref)) {
            throw new ValidationException(lang('Editor.invalidPayload'));
        }

        foreach ($blocks as $block) {
            if (! is_array($block) || array_is_list($block)) {
                throw new ValidationException(lang('Editor.invalidPayload'));
            }
        }

        /** @var list<array<string, mixed>> $blocks */
        return new self($lang, $type, is_string($ref) ? $ref : null, $blocks);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return ['lang' => $this->lang, 'scope' => ['type' => $this->scopeType, 'ref' => $this->scopeRef], 'blocks' => $this->blocks];
    }
}
