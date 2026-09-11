<?php

declare(strict_types=1);

return [
    'snapshotUnavailable' => 'The editor could not read a consistent version of this content.',
    'snapshotIncomplete'  => 'The editor could not finish reading this content.',
    'snapshotNested'      => 'The editor cannot read a document from inside another transaction.',
    'invalidPayload' => 'The editor document contains invalid changes.',
    'invalidTree' => 'The block structure is not valid.',
    'protectedBlock' => 'A required or protected block cannot be removed.',
    'invalidField' => "A field value does not match the block's definition.",
    'conflict' => 'This content changed in another session. Review the current version before saving.',
    'busy' => 'This content is being updated. Try again in a moment.',
    'patchNested'         => 'The editor cannot save a document from inside another transaction.',
];
