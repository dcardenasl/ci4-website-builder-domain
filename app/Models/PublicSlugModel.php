<?php

declare(strict_types=1);

namespace App\Models;

use dcardenasl\Ci4ApiCore\Models\BasePublicSlugModel;

/** Consumer-owned model for the generic public-slug sidecar. */
final class PublicSlugModel extends BasePublicSlugModel
{
    protected $table = 'public_slugs';
}
