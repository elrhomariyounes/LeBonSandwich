<?php


namespace lbs\catalogue\Middlewares;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AuthorizationMiddleware extends Middleware
{
    public function __invoke(Request $rq, Response $rs, $next)
    {
        $apiKey = $rq->getQueryParams('APIKEY',"");
        if(count($apiKey)==0){
            $error = [
                "type"=>"error",
                "error"=>401,
                "message"=>"Not Authorized. No API Key provided"
            ];
            $rs = $rs->withStatus(401)->withHeader("Content-Type","application/json;charset=utf-8");
            $rs->getBody()->write(json_encode($error));
            return $rs;
        }
        if($apiKey!=$this->container->settings['APIKEY']){
            $error = [
                "type"=>"error",
                "error"=>401,
                "message"=>"Not Authorized !!"
            ];
            $rs = $rs->withStatus(401)->withHeader("Content-Type","application/json;charset=utf-8");
            $rs->getBody()->write(json_encode($error));
            return $rs;
        }

        // if match the api key
        return $next($rq,$rs);
    }
}