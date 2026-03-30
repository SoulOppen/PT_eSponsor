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
                [
                    'key' => 'items',
                    'type' => 'repeater',
                    'label' => 'Enlaces',
                    'required' => true,
                    'subfields' => [
                        ['key' => 'label', 'type' => 'text', 'label' => 'Texto del botón'],
                        ['key' => 'url', 'type' => 'text', 'label' => 'URL'],
                    ],
                ],
                ['key' => 'color', 'type' => 'color', 'label' => 'Color fondo', 'default' => '#1e293b'],
                ['key' => 'text_color', 'type' => 'color', 'label' => 'Color texto', 'default' => '#ffffff'],
            ],
        ],
        'image' => [
            'label' => 'Imagen',
            'icon' => 'image',
            'fields' => [
                ['key' => 'url', 'type' => 'text', 'label' => 'URL de imagen', 'required' => true],
                ['key' => 'alt', 'type' => 'text', 'label' => 'Texto alternativo'],
            ],
        ],
        'video' => [
            'label' => 'Video',
            'icon' => 'video',
            'fields' => [
                ['key' => 'url', 'type' => 'text', 'label' => 'URL del video', 'required' => true],
            ],
        ],
        'social' => [
            'label' => 'Redes sociales',
            'icon' => 'social',
            'fields' => [
                [
                    'key' => 'links',
                    'type' => 'repeater',
                    'label' => 'Enlaces',
                    'required' => false,
                    'subfields' => [
                        [
                            'key' => 'network',
                            'type' => 'select',
                            'label' => 'Red social',
                            'options' => ['instagram', 'tiktok', 'youtube', 'facebook', 'x', 'otra'],
                        ],
                        ['key' => 'custom_network', 'type' => 'text', 'label' => 'Nombre de la red (si eliges otra)'],
                        ['key' => 'url', 'type' => 'text', 'label' => 'URL'],
                    ],
                ],
            ],
        ],
        'music' => [
            'label' => 'Música',
            'icon' => 'music',
            'fields' => [
                [
                    'key' => 'platform',
                    'type' => 'select',
                    'label' => 'Plataforma',
                    'required' => true,
                    'options' => ['spotify', 'bandcamp', 'soundcloud'],
                ],
                ['key' => 'url', 'type' => 'text', 'label' => 'URL', 'required' => true],
            ],
        ],
    ],
];
