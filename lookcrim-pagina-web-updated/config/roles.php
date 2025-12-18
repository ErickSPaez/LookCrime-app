<?php

return [
    // Default permissions per role (solo módulos actuales)
    'definitions' => [
        'user' => [
            'manage_publications' => false,
            'manage_projects' => false,
            'manage_users' => false,
            'manage_settings' => false,
        ],
        'moderador' => [
            'manage_publications' => true,
            'manage_projects' => false,
            'manage_users' => false,
            'manage_settings' => false,
        ],
        'programador' => [
            'manage_publications' => true,
            'manage_projects' => true,
            'manage_users' => false,
            'manage_settings' => true,
        ],
        'front_end' => [
            'manage_publications' => true,
            'manage_projects' => true,
            'manage_users' => false,
            'manage_settings' => false,
        ],
        'back_end' => [
            'manage_publications' => true,
            'manage_projects' => true,
            'manage_users' => true,
            'manage_settings' => true,
        ],
        'developer' => [
            'manage_publications' => true,
            'manage_projects' => true,
            'manage_users' => true,
            'manage_settings' => true,
        ],
        'admin' => [
            'manage_publications' => true,
            'manage_projects' => true,
            'manage_users' => true,
            'manage_settings' => true,
        ],
        'super_usuario' => [
            'manage_publications' => true,
            'manage_projects' => true,
            'manage_users' => true,
            'manage_settings' => true,
        ],
    ],
];
