<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Block type schemas (field definitions for the page builder)
    |--------------------------------------------------------------------------
    */
    'schemas' => [
        'text' => [
            'label' => 'Texto',
            'icon' => 'text',
            'fields' => [
                ['key' => 'content', 'type' => 'textarea', 'label' => 'Contenido', 'required' => true],
                ['key' => 'align', 'type' => 'select', 'label' => 'Alineación', 'options' => ['left', 'center', 'right']],
            ],
        ],
        'links' => [
            'label' => 'Enlaces',
            'icon' => 'link',
            'fields' => [
                ['key' => 'title', 'type' => 'text', 'label' => 'Título', 'required' => true],
                ['key' => 'url', 'type' => 'url', 'label' => 'URL', 'required' => true],
            ],
        ],
        'image' => [
            'label' => 'Imagen',
            'icon' => 'image',
            'fields' => [
                ['key' => 'url', 'type' => 'url', 'label' => 'URL de imagen', 'required' => true],
                ['key' => 'alt', 'type' => 'text', 'label' => 'Texto alternativo'],
            ],
        ],
        'video' => [
            'label' => 'Video',
            'icon' => 'video',
            'fields' => [
                ['key' => 'url', 'type' => 'url', 'label' => 'URL del video', 'required' => true],
            ],
        ],
        'social' => [
            'label' => 'Redes sociales',
            'icon' => 'social',
            'fields' => [
                ['key' => 'links', 'type' => 'repeater', 'label' => 'Enlaces'],
            ],
        ],
        'music' => [
            'label' => 'Música',
            'icon' => 'music',
            'fields' => [
                ['key' => 'platform', 'type' => 'text', 'label' => 'Plataforma', 'required' => true],
                ['key' => 'url', 'type' => 'url', 'label' => 'URL', 'required' => true],
            ],
        ],
    ],
];
