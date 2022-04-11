<?php
return [
    'settings' => [
        'displayErrorDetails' => true, // set to false in production
        'addContentLengthHeader' => false, // Allow the web server to send the content-length header

        // Renderer settings
        'renderer' => [
            'template_path' => __DIR__ . '/../templates/',
        ],

        'profile_images' => [
            'directory' => 'assets/profile_images',
        ],

        'public_path_disk' => [
            'directory' => dirname(realpath(__DIR__)) . '/public/',
        ],

        // Monolog settings
        'logger' => [
            'name' => 'slim-app',
            'path' => isset($_ENV['docker']) ? 'php://stdout' : __DIR__ . '/../logs/app.log',
            'level' => \Monolog\Logger::DEBUG,
        ],

        //Mail settings
        'mail' => [
          'hostname' => '',
          'username' => '',
          'password' => '',

        ],

        //DB settings
        "db" => [
          "host" => "",
          "dbname" => "",
          "user" => "",
          "pass" => ""
        ],
    ],
];
