<?php

declare(strict_types=1);

return [
    'create_success' => 'Menu created successfully.',
    'update_success' => 'Menu updated successfully.',
    'delete_success' => 'Menu deleted successfully.',
    'not_found'      => 'Menu not found.',
    'fields'         => [
        'menu_key' => 'Menu Key',
        'menu_key_placeholder' => 'Enter Menu Key',
        'menu_key_help' => 'Enter Menu Key.',
        'location' => 'Location',
        'location_placeholder' => 'Enter Location',
        'location_help' => 'Enter Location.',
        'is_active' => 'Is Active',
        'is_active_placeholder' => 'Toggle Is Active',
        'is_active_help' => 'Toggle Is Active.',
    ],
    'menu_key_must_be_unique' => 'Menu key must be unique.',
    'menu_key_already_taken' => "The menu key '{0}' is already taken.",
    'invalid_hierarchy' => 'Invalid hierarchy.',
    'cannot_be_own_parent' => 'A menu item cannot be its own parent.',
    'parent_not_exists' => 'Parent menu item does not exist.',
    'circular_reference' => 'Circular reference detected: this item is an ancestor of the proposed parent.',
    'invalid_link_type' => 'The selected link type properties are invalid for this menu item.',
    'menu_not_found' => 'The parent menu does not exist.',
    'parent_different_menu' => 'Parent menu item belongs to a different menu.',
    'duplicate_menu_item' => 'A menu item with the same destination already exists in this menu.',
];
