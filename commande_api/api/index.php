<?php
use \lbs\command\Helpers\DataBaseHelper;
use \lbs\command\control\OrderController;
use lbs\command\Middleware\AuthMiddleware;
use lbs\command\Middleware\JWTMiddleware;
use \lbs\command\Middleware\TokenMiddleware;
require '../src/vendor/autoload.php';

//Require settings files for Slim Container
$settings = require_once "../src/conf/GlobalSettings.php";
$errorHandlers=require_once "../src/conf/errorHandlers.php";

//Slim container Config
$config = array_merge($settings,$errorHandlers);
$c = new \Slim\Container($config);
$app = new \Slim\App($c);

//Start Eloquent Connection
DataBaseHelper::ConnectToDatabase($app->getContainer()->settings['dbConf']);

//TODO Delete (APP HEALTH Status)
$app->get('/Hello[/]', function($rq,$rs,$args) use ($c){
    $rs=$rs->withStatus(200)
        ->withHeader('Content-type','application/json');
    $rs->getBody()->write(json_encode(["message"=>"Hello Younes"]));
});

//Get Orders routes
$app->get('/Orders[/]', function($rq,$rs,$args) use ($c){
    return (new OrderController($c))->GetOrders($rq,$rs,$args);
})->setName('orders');

//Get Order by id
$app->get('/Orders/{id}[/]', function($rq,$rs,$args) use ($c){
    return (new OrderController($c))->GetOrder($rq,$rs,$args);
})->setName('order')->add(new TokenMiddleware($c));


//Add order
$app->post('/Orders[/]',function($rq,$rs,$args) use ($c){
    return (new OrderController($c))->AddOrder($rq,$rs,$args);
})->add(new \lbs\command\Middleware\ClientMiddleware($c));

//Update an order
$app->put('/Orders/{id}[/]',function($rq,$rs,$args) use ($c){
    return (new OrderController($c))->UpdateOrder($rq,$rs,$args);
});

//Sign in client
$app->post('/clients/{id}/auth',function($rq,$rs,$args) use ($c){
   return (new OrderController($c))->AuthClient($rq,$rs,$args);
})->add(new AuthMiddleware($c));

//Get Client by id
$app->get('/clients/{id}[/]', function($rq,$rs,$args) use ($c){
    return (new OrderController($c))->GetClientById($rq,$rs,$args);
})->add(new JWTMiddleware($c));

// Run the application
$app->run();
