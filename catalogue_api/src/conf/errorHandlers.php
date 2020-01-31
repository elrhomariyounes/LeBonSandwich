<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

return [
    'notFoundHandler' => function ($container) {
        return function (Request $request, Response $response) use ($container){
            $response = $response->withStatus(400)->withHeader('Content-Type', 'application/json;charset=utf-8');
            $response->getBody()->write(json_encode([
                "type" => "error",
                "error" => 400,
                "message" => "Bad Request ! Not well formed request"
            ]));

            return $response;
        };
    },
    'notAllowedHandler' => function ($container) {
        return function (Request $request, Response $response, $allowed_methods) use ($container){
            $response = $response->withStatus(405)->withHeader('Content-Type', 'application/json;charset=utf-8');
            $response->getBody()->write(json_encode([
                "type" => "error",
                "error" => 405,
                "message" => "Not allowed methods : ".implode(', ', $allowed_methods)
            ]));

            return $response;
        };
    },
    'phpErrorHandler' => function ($container) {
        return function (Request $request, Response $response, \Error $exception) use ($container){
            $response = $response->withStatus(500)->withHeader('Content-Type', 'application/json;charset=utf-8');
            $response->getBody()->write(json_encode([
                "type" => "error",
                "error" => 500,
                "message" => "Error ! Internal server error : ".$exception->getMessage()." dans le fichier : ".$exception->getFile()." à la ligne : ".$exception->getLine()
            ]));

            return $response;
        };
    }
];