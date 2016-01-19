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
            'base_url' => "http://dev.ugho-stephan.fr/",
            'picture_path' => __DIR__ . '/../public/picture_uploaded/',
            'absolute_picture_path' => 'http://dev.ugho-stephan.fr/picture_uploaded/',
            'extensions_valids' => [ 'jpg' , 'jpeg' , 'gif' , 'png' ],
            'secretToken' => 'Ok2CCaEaHngyNPMqPRcE5MkvzIAwpnrJc5zECIO9fAW9dnxI1zppPvRKu7pnU8tbeFrjjke5m8wDacadWWWOFgiLcr1xvdxhUVMA1WWTJkLiFFmFAAGwS',
        ],
    ],
];
