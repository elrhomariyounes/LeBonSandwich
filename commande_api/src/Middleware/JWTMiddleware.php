<?php


namespace lbs\command\Middleware;
use Firebase\JWT\JWT;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use lbs\command\model\Client;
class JWTMiddleware extends Middleware
{
    public function __invoke(Request $rq, Response $rs, $next)
    {
        $key=$this->container->settings['key'];
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
            $tokenString=substr($header,7);
            try{
                $decodedToken = JWT::decode($tokenString,$key,['HS256']);
                $rq=$rq->withAttribute('token',json_encode($decodedToken));
                return $next($rq,$rs);
            }catch (\UnexpectedValueException $ex){
                $error = [
                    "type"=>"error",
                    "error"=>505,
                    "message"=>$ex->getMessage()
                ];
                $rs->getBody()->write(json_encode($error));
                $rs=$rs->WithStatus(505);
                $rs = $rs->withHeader('Content-type', 'application/json');
                return $rs;
            }

        }
    }
}