<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

require '../src/vendor/autoload.php';

$db = new \Illuminate\Database\Capsule\Manager();

$array = parse_ini_file("../src/conf/config.ini");

$db->addConnection($array);
$db->setAsGlobal();
$db->bootEloquent();

$config = [
    'settings' => [
        'displayErrorDetails' => true,
    ]
];

$c = new \Slim\Container($config);


$app = new \Slim\App($c);

$app->get('/Orders', "\lbs\command\control\OrderController:GetOrders")->setName('orders');
$app->get('/Orders/{id}', "\lbs\command\control\OrderController:GetOrder")->setName('order');
$app->post('/Orders',"\lbs\command\control\OrderController:AddOrder");


$app->run();
