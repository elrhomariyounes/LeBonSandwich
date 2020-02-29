<?php
/**
 * Created by PhpStorm.
 * User: Younes
 * Date: 28/02/2020
 * Time: 00:24
 */

namespace lbs\command\Middleware;
use lbs\command\model\Client;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AuthMiddleware extends Middleware
{
    public function __invoke(Request $rq, Response $rs, $next){
        // Return error if there is no Authorization Header
        $header = $rq->getHeader('Authorization');
        if(count($header)==0){
            $error = [
                "type"=>"error",
                "error"=>401,
                "message"=>"No authorization header present !!"
            ];
            $rs->getBody()->write(json_encode($error));
            $rs=$rs->WithStatus(401);
            $rs = $rs->withHeader('Content-type', 'application/json');
            return $rs;
        }
        else{
            $header = $rq->getHeaderLine('Authorization');
            $credentials = explode(':',base64_decode(substr($header,6)),2);
            $route = $rq->getAttribute('route');
            $id=$route->getArguments();
            if(count($credentials)==2){
                list($us,$pw)= $credentials;
                $client = Client::select('id','nom_client','passwd')->where('id','=',$id)->first();
                if($client!=null){
                    if(!password_verify($pw,$client->passwd)){
                        $error = [
                            "type"=>"error",
                            "error"=>401,
                            "message"=>"Credentials are not valid !!"
                        ];
                        $rs->getBody()->write(json_encode($error));
                        $rs=$rs->WithStatus(401);
                        $rs = $rs->withHeader('Content-type', 'application/json');
                        return $rs;
                    }
                    $rq = $rq->withAttribute('client_id',$id);
                    return $next($rq,$rs);
                }
                $error = [
                    "type"=>"error",
                    "error"=>404,
                    "message"=>"No client found with this id !!"
                ];

                $rs->getBody()->write(json_encode($error));
                $rs=$rs->WithStatus(404);
                $rs = $rs->withHeader('Content-type', 'application/json');
                return $rs;
            }
        }

    }

}