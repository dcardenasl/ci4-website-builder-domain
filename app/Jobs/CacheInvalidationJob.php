<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Libraries\Cms\CacheInvalidationClient;
use dcardenasl\Ci4ApiCore\Queue\Job;

final class CacheInvalidationJob extends Job
{
    public function handle(): void
    {
        $scopes = $this->data['scopes'] ?? [];
        if (! is_array($scopes)) {
            throw new \InvalidArgumentException('Cache invalidation scopes must be an array.');
        }

        $normalizedScopes = array_values(array_filter(
            array_map(static fn (mixed $scope): string => trim((string) $scope), $scopes),
            static fn (string $scope): bool => $scope !== '',
        ));

        (new CacheInvalidationClient(dispatch: false))->invalidateNow($normalizedScopes);
    }
}
