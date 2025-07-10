<?php
require 'vendor/autoload.php';

$auth = new Controllers\AuthController(
    \Slim\Views\Twig::create(__DIR__ . '/src/Views', ['cache' => false]),
    new \Medoo\Medoo([
        'type' => 'mysql',
        'host' => 'localhost',
        'database' => 'knowledge_system',
        'username' => 'root',
        'password' => ''
    ])
);

echo "Berhasil load AuthController!";
