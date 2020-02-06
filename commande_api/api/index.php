<?php
use \lbs\command\Helpers\DataBaseHelper;
use \lbs\command\control\OrderController;
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

$app->get('/Orders[/]', function($rq,$rs,$args) use ($c){
    return (new OrderController($c))->GetOrders($rq,$rs,$args);
})->setName('orders');

$app->get('/Orders/{id}[/]', function($rq,$rs,$args) use ($c){
    return (new OrderController($c))->GetOrder($rq,$rs,$args);
})->setName('order')->add(new TokenMiddleware($c));

$app->post('/Orders[/]',function($rq,$rs,$args) use ($c){
    return (new OrderController($c))->AddOrder($rq,$rs,$args);
});

$app->put('/Orders/{id}[/]',function($rq,$rs,$args) use ($c){
    return (new OrderController($c))->UpdateOrder($rq,$rs,$args);
});

$app->run();
