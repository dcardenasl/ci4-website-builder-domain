<?php

declare(strict_types=1);

namespace App\Libraries\Cms;

/**
 * Holds cache invalidations until the editor's transaction commits.
 *
 * The block service invalidates after each individual write. Inside a batch that
 * means a partial failure would already have told the public site to drop its
 * cache for a document that was then rolled back. Buffering here keeps the two
 * in step: nothing is announced until the whole batch is durable.
 */
final class EditorCacheInvalidationClient extends CacheInvalidationClient
{
    /** @var array<string, string> */
    private array $pending = [];

    /** @param list<string> $scopes */
    public function invalidate(array $scopes): void
    {
        foreach ($scopes as $scope) {
            $this->pending[$scope] = $scope;
        }
    }

    public function discard(): void
    {
        $this->pending = [];
    }

    public function flush(): void
    {
        $scopes = array_values($this->pending);
        $this->pending = [];

        if ($scopes !== []) {
            parent::invalidate($scopes);
        }
    }
}
