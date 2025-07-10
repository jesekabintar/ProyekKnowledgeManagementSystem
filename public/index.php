<?php
use Slim\Factory\AppFactory;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;

require __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create();

$twig = Twig::create(__DIR__ . '/../src/Views', ['cache' => false]);
$app->add(TwigMiddleware::create($app, $twig));

(require __DIR__ . '/../routes/web.php')($app);

$app->run();
