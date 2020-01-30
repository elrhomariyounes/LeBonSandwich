<?php

use lbs\catalogue\Controller\SandwichController;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
require_once "../src/vendor/autoload.php";

$app = new \Slim\App();

$app->get('/categories/{id}/sandwichs', SandwichController::class.':GetSandwichesByCategorie');
$app->run();
