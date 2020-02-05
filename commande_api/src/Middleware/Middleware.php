<?php

namespace lbs\command\Middleware;
use \Slim\Container;
class Middleware
{
    private $container;

    public function __construct($container)
    {
        $this->container = $container;
    }
}