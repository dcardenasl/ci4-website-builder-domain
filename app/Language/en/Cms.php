<?php

declare(strict_types=1);

return [
    'languages' => [
        'cannot_deactivate_default'         => 'Cannot deactivate the default language.',
        'must_have_default'                 => 'Must have at least one default language. Mark another language as default instead.',
        'cannot_delete_default'             => 'Cannot delete the default language.',
        'code_must_be_unique'               => 'Language code must be unique.',
        'code_already_taken'                => "The language code '{0}' is already taken.",
    ],
    'block_types' => [
        'block_key_must_be_unique'          => 'Block key must be unique.',
        'block_key_already_taken'           => "The block key '{0}' is already taken.",
        'in_use'                             => 'Cannot delete: this block type is in use by {0} instance(s) — {1}. Remove or replace those blocks first.',
        'usage_page'                         => 'Page',
        'usage_entry'                        => 'Entry',
        'usage_instance'                     => 'instance',
    ],
    'settings' => [
        'create_success' => 'Setting created successfully.',
        'update_success' => 'Setting updated successfully.',
        'delete_success' => 'Setting deleted successfully.',
    ],
    'file_references' => [
        'missing_table' => 'Required table `cms_file_references` is missing. Run the domain migrations.',
        'unsupported_resource' => 'Unsupported CMS file reference resource type: {0}.',
        'sync_failed' => 'Unable to synchronize CMS file references for {0} #{1}.',
    ],
    'public_filters' => [
        'object' => 'Each filter must be an object.',
        'field_not_allowed' => 'The filter field is not allowed.',
        'operator_not_allowed' => 'The filter operator is not allowed for this field.',
        'in_requires_list' => 'The in operator requires a non-empty list.',
        'in_too_many' => 'The in operator accepts at most 20 values.',
        'scalar_required' => 'This operator requires one scalar value.',
        'value_scalar' => 'The filter value must be scalar.',
        'value_required' => 'The filter value cannot be empty.',
        'value_too_long' => 'The filter value is too long.',
        'list_required' => 'Filters must be a list.',
        'too_many' => 'At most 6 filters are allowed.',
        'unsupported_field' => 'Unsupported public entry filter field.',
        'escape_invalid' => 'The database driver returned an invalid escaped filter value.',
        'escape_like_invalid' => 'The database driver returned an invalid escaped LIKE value.',
    ],
];
