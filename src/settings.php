<?php
return [
    'settings' => [
        'displayErrorDetails' => true,

        // Renderer settings
        'renderer' => [
            'template_path' => __DIR__ . '/../templates/',
        ],

        // Monolog settings
        'logger' => [
            'name' => 'slim-app',
            'path' => __DIR__ . '/../logs/app.log',
        ],

        // Settings for Controlleur
        'config' => [
            'base_url' => "https://dev.ugho-stephan.fr/",
            'picture_path' => __DIR__ . '/../public/picture_uploaded/',
            'absolute_picture_path' => 'https://dev.ugho-stephan.fr/picture_uploaded/',
            'extensions_valids' => [ 'jpg' , 'jpeg' , 'gif' , 'png' ],
            'secretToken' => 'SECRET_TOKEN',
            'googleApiKey' => 'GOOGLE_API_KEY',
        ],
    ],
];
