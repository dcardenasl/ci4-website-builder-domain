<?php

declare(strict_types=1);

return [
    'create_success' => 'Menu creado(a) exitosamente.',
    'update_success' => 'Menu actualizado(a) exitosamente.',
    'delete_success' => 'Menu eliminado(a) exitosamente.',
    'not_found'      => 'Menu no encontrado(a).',
    'fields'         => [
        'menu_key' => 'Menu Key',
        'menu_key_placeholder' => 'Ingresa Menu Key',
        'menu_key_help' => 'Ingresa Menu Key.',
        'location' => 'Location',
        'location_placeholder' => 'Ingresa Location',
        'location_help' => 'Ingresa Location.',
        'is_active' => 'Is Active',
        'is_active_placeholder' => 'Activa o desactiva Is Active',
        'is_active_help' => 'Activa o desactiva Is Active.',
    ],
    'menu_key_must_be_unique' => 'La clave del menú debe ser única.',
    'menu_key_already_taken' => "La clave del menú '{0}' ya está en uso.",
    'invalid_hierarchy' => 'Jerarquía inválida.',
    'cannot_be_own_parent' => 'Un elemento de menú no puede ser su propio elemento superior.',
    'parent_not_exists' => 'El elemento superior del menú no existe.',
    'circular_reference' => 'Referencia circular detectada: este elemento es un ancestro del elemento superior propuesto.',
    'invalid_link_type' => 'Las propiedades del tipo de enlace seleccionado no son válidas para este elemento de menú.',
    'menu_not_found' => 'El menú superior no existe.',
    'parent_different_menu' => 'El elemento de menú superior pertenece a un menú diferente.',
    'duplicate_menu_item' => 'Ya existe un elemento de menú con el mismo destino en este menú.',
];
