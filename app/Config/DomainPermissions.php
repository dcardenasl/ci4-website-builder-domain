<?php

declare(strict_types=1);

namespace Config;

/**
 * Source of truth for the permissions exposed by this website builder app.
 *
 * Add an entry here, then run:
 *
 *     php spark domain:sync-permissions
 *
 * to register them in the hub. The command is idempotent — pre-existing codes
 * are left untouched.
 *
 * Permission codes use `.` as separator (NOT `:`) because CodeIgniter splits
 * filter arguments on `:` (`permission:foo:bar` would be parsed as filter=foo,
 * arg=[bar], silently dropping the rest).
 */
class DomainPermissions
{
    /**
     * @var list<array{code: string, resource: string, action: string, description?: string}>
     */
    public const PERMISSIONS = [
        // Pages
        ['code' => 'cms.pages.read', 'resource' => 'pages', 'action' => 'read', 'description' => 'Read CMS pages'],
        ['code' => 'cms.pages.write', 'resource' => 'pages', 'action' => 'write', 'description' => 'Create/Edit CMS pages'],
        ['code' => 'cms.pages.admin', 'resource' => 'pages', 'action' => 'admin', 'description' => 'Full administration of CMS pages'],

        // Menus
        ['code' => 'cms.menus.read', 'resource' => 'menus', 'action' => 'read', 'description' => 'Read CMS menus'],
        ['code' => 'cms.menus.write', 'resource' => 'menus', 'action' => 'write', 'description' => 'Create/Edit CMS menus'],
        ['code' => 'cms.menus.admin', 'resource' => 'menus', 'action' => 'admin', 'description' => 'Full administration of CMS menus'],

        // Blocks
        ['code' => 'cms.blocks.read', 'resource' => 'blocks', 'action' => 'read', 'description' => 'Read CMS content blocks'],
        ['code' => 'cms.blocks.write', 'resource' => 'blocks', 'action' => 'write', 'description' => 'Create/Edit CMS content blocks'],
        ['code' => 'cms.blocks.admin', 'resource' => 'blocks', 'action' => 'admin', 'description' => 'Full administration of CMS content blocks'],

        // Collections
        ['code' => 'cms.collections.read', 'resource' => 'collections', 'action' => 'read', 'description' => 'Read CMS custom collections'],
        ['code' => 'cms.collections.write', 'resource' => 'collections', 'action' => 'write', 'description' => 'Create/Edit CMS custom collections'],
        ['code' => 'cms.collections.admin', 'resource' => 'collections', 'action' => 'admin', 'description' => 'Full administration of CMS custom collections'],

        // Entries
        ['code' => 'cms.entries.read', 'resource' => 'entries', 'action' => 'read', 'description' => 'Read CMS entries'],
        ['code' => 'cms.entries.write', 'resource' => 'entries', 'action' => 'write', 'description' => 'Create/Edit CMS entries'],
        ['code' => 'cms.entries.admin', 'resource' => 'entries', 'action' => 'admin', 'description' => 'Full administration of CMS entries'],

        // Categories
        ['code' => 'cms.categories.read', 'resource' => 'categories', 'action' => 'read', 'description' => 'Read CMS entry categories'],
        ['code' => 'cms.categories.write', 'resource' => 'categories', 'action' => 'write', 'description' => 'Create/Edit CMS entry categories'],
        ['code' => 'cms.categories.admin', 'resource' => 'categories', 'action' => 'admin', 'description' => 'Full administration of CMS entry categories'],

        // Tags
        ['code' => 'cms.tags.read', 'resource' => 'tags', 'action' => 'read', 'description' => 'Read CMS tags'],
        ['code' => 'cms.tags.write', 'resource' => 'tags', 'action' => 'write', 'description' => 'Create/Edit CMS tags'],
        ['code' => 'cms.tags.admin', 'resource' => 'tags', 'action' => 'admin', 'description' => 'Full administration of CMS tags'],

        // Settings
        ['code' => 'cms.settings.read', 'resource' => 'settings', 'action' => 'read', 'description' => 'Read CMS settings'],
        ['code' => 'cms.settings.write', 'resource' => 'settings', 'action' => 'write', 'description' => 'Create/Edit CMS settings'],
        ['code' => 'cms.settings.admin', 'resource' => 'settings', 'action' => 'admin', 'description' => 'Full administration of CMS settings'],

        // Languages
        ['code' => 'cms.languages.read', 'resource' => 'languages', 'action' => 'read', 'description' => 'Read CMS languages'],
        ['code' => 'cms.languages.write', 'resource' => 'languages', 'action' => 'write', 'description' => 'Create/Edit CMS languages'],
        ['code' => 'cms.languages.admin', 'resource' => 'languages', 'action' => 'admin', 'description' => 'Full administration of CMS languages'],

        // Redirects
        ['code' => 'cms.redirects.read', 'resource' => 'redirects', 'action' => 'read', 'description' => 'Read CMS redirects'],
        ['code' => 'cms.redirects.write', 'resource' => 'redirects', 'action' => 'write', 'description' => 'Create/Edit CMS redirects'],
        ['code' => 'cms.redirects.admin', 'resource' => 'redirects', 'action' => 'admin', 'description' => 'Full administration of CMS redirects'],

        // Form Submissions
        ['code' => 'cms.submissions.read', 'resource' => 'submissions', 'action' => 'read', 'description' => 'Read CMS form submissions'],
        ['code' => 'cms.submissions.write', 'resource' => 'submissions', 'action' => 'write', 'description' => 'Manage CMS form submissions (update status)'],

        // Forms
        ['code' => 'cms.forms.read', 'resource' => 'forms', 'action' => 'read', 'description' => 'Read CMS dynamic forms'],
        ['code' => 'cms.forms.write', 'resource' => 'forms', 'action' => 'write', 'description' => 'Create/Edit CMS dynamic forms and fields'],
        ['code' => 'cms.forms.admin', 'resource' => 'forms', 'action' => 'admin', 'description' => 'Full administration of CMS dynamic forms (delete)'],

        // Analytics
        ['code' => 'cms.analytics.read', 'resource' => 'analytics', 'action' => 'read', 'description' => 'View website analytics and visit statistics'],
    ];
}
