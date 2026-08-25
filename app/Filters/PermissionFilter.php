<?php

declare(strict_types=1);

namespace App\Filters;

use dcardenasl\Ci4ApiCore\Contracts\SecurityAuditLoggerInterface;
use dcardenasl\Ci4ApiCore\Http\Filters\AbstractPermissionFilter;

/**
 * Domain-specific permission filter — delegates the entire policy to
 * `AbstractPermissionFilter`. This domain has no security audit logger, so
 * `getSecurityAuditLogger()` returns `null` (access control is still
 * enforced; only the audit trail is skipped).
 *
 * `superAdminBypassCode()` lets a platform-level superadmin satisfy any
 * `permission:<code>` requirement without the code having been explicitly
 * assigned to their role yet — domain permissions are registered in the hub
 * separately from role assignment, and an introspection cache can also be
 * briefly stale after a role change. Without the bypass, a superadmin could
 * be locked out of a domain the moment it registers a new permission.
 */
class PermissionFilter extends AbstractPermissionFilter
{
    protected function getSecurityAuditLogger(): ?SecurityAuditLoggerInterface
    {
        return null;
    }

    protected function superAdminBypassCode(): ?string
    {
        return 'iam.superadmin-access';
    }

    // The base class's defaults read `Auth.authRequired`/`Auth.insufficientPermissions`,
    // which this app's `app/Language/{es,en}/Auth.php` does not define (only
    // `rateLimitExceeded`, used by ThrottleFilter). This app's own `Api.php`
    // language file already carries these two keys — override to keep using
    // them rather than silently falling back to the package's English-only
    // `Auth.php` copy.
    protected function unauthenticatedMessage(): string
    {
        return (string) lang('Api.authRequired');
    }

    protected function forbiddenMessage(): string
    {
        return (string) lang('Api.insufficientPermissions');
    }
}
