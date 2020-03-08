<?php
use \lbs\suiviCommande\Helpers\DataBaseHelper;
use \lbs\suiviCommande\Controller\OrderController;
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

$app->get('/orders[/]', function ($rq, $rs,$args) use ($c){
    return (new OrderController($c))->GetOrders($rq,$rs,$args);
});

$app->get('/orders/{id}[/]',function ($rq, $rs,$args) use ($c){
    return (new OrderController($c))->GetOrderById($rq,$rs,$args);
});

$app->put('/orders/{id}[/]',function ($rq, $rs,$args) use ($c){
    return (new OrderController($c))->UpdateOrderStatus($rq,$rs,$args);
});
$app->run();
