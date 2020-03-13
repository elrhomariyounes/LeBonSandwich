<?php

use lbs\catalogue\Middlewares\AuthorizationMiddleware;
use lbs\catalogue\Middlewares\CorsMiddleware;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use lbs\catalogue\Controller\CatalogController as CatalogController;

require_once "../src/vendor/autoload.php";

// Require settings files
$globalSettings = require_once "../src/conf/GlobalSettings.php";
$errorHandlers = require_once "../src/conf/errorHandlers.php";

//Slim Container configuration
$containerConfig = array_merge($globalSettings,$errorHandlers);
$container = new \Slim\Container($containerConfig);
$app = new \Slim\App($container);


//CORS Middleware
$app->options('/{routes:.+}', function ($request, $response, $args) {
    return $response;
});
$app->add(new CorsMiddleware($container));

//Routes

//Get all the categories
$app->get('/categories[/]',function ($rq,$rs,$args) use($container){
    return (new CatalogController($container))->GetAllCategories($rq,$rs,$args);
});

//Get categorie by id
$app->get('/categories/{id}[/]',function ($rq,$rs,$args) use($container){
    return (new CatalogController($container))->GetCategorieById($rq,$rs,$args);
});

//Get all sandwiches
$app->get('/sandwiches[/]',function ($rq,$rs,$args) use($container){
    return (new CatalogController($container))->GetAllSandwiches($rq,$rs,$args);
});

//Get sandwich by Ref
$app->get('/sandwiches/{id}[/]',function ($rq,$rs,$args) use($container){
    return (new CatalogController($container))->GetSandwichByRef($rq,$rs,$args);
});

// Get sandwiches by category
$app->get('/categories/{id}/sandwiches[/]',function ($rq,$rs,$args) use($container){
    return (new CatalogController($container))->GetSandwichesByCategorie($rq,$rs,$args);
});

//Delete Sandwich
$app->delete('/sandwiches/{id}[/]',function ($rq,$rs,$args) use($container){
    return (new CatalogController($container))->DeleteSandwich($rq,$rs,$args);
})->add(new AuthorizationMiddleware());


$app->run();
