<?php

declare(strict_types=1);

namespace App\Filters;

use dcardenasl\Ci4ApiCore\Http\Filters\AbstractHubSignatureFilter;

/**
 * Authenticates the Hub's reverse calls into this Domain app.
 *
 * X-App-Key cannot be reused in this direction because the Hub stores app keys
 * as one-way material. The dedicated shared secret is therefore only used by
 * the two internal file routes and is never exposed to public callers.
 */
class HubSignatureFilter extends AbstractHubSignatureFilter
{
    protected function hubSecret(): string
    {
        return (string) config('Hub')->internalSecret;
    }
}
