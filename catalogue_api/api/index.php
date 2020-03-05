<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use lbs\catalogue\Controller\CatalogController as CatalogController;
require_once "../src/vendor/autoload.php";
//Slim Container configuration
$config=['settings' => [
    'displayErrorDetails' => true
]];
$errorHandlers = require_once "../src/conf/errorHandlers.php";
$containerConfig = array_merge($config,$errorHandlers);
$container = new \Slim\Container($containerConfig);
$app = new \Slim\App($container);
//CORS Middleware
$app->options('/{routes:.+}', function ($request, $response, $args) {
    return $response;
});
$app->add(new \lbs\catalogue\Middlewares\CorsMiddleware($container));

//Routes
$app->get('/categories/{id}/sandwiches[/]',function ($rq,$rs,$args) use($container){
    return (new CatalogController($container))->GetSandwichesByCategorie($rq,$rs,$args);
});

$app->get('/categories/{id}[/]',function ($rq,$rs,$args) use($container){
    return (new CatalogController($container))->GetCategorieById($rq,$rs,$args);
});

$app->get('/sandwiches/{id}[/]',function ($rq,$rs,$args) use($container){
    return (new CatalogController($container))->GetSandwichByRef($rq,$rs,$args);
});
$app->run();
