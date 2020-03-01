<?php


namespace lbs\command\Middleware;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Firebase\JWT\JWT;
use lbs\command\model\Client;

class ClientMiddleware extends Middleware
{
    public function __invoke(Request $rq, Response $rs, $next)
    {
        //TODO : save in config file
        $key="MYSECUREKEY";
        // TODO:Get the parsed body
        // 2 - if clientId passed in body check if there is a token jwt
        // 3 - no token => not authorized 401
        // 4 - decode token
        // 5 - token not matching client id => not authorized 401
        // 6 - token matched => $next($rq,$rs)
        $parsedBody = $rq->getParsedBody();
        if(isset($parsedBody['clientId'])){
            //No Auth Header
            if(count($rq->getHeader('Authorization'))==0){
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
                    //Not matching token
                    if($parsedBody['clientId']!=$decodedToken->data->clientId->id){
                        $error = [
                            "type"=>"error",
                            "error"=>401,
                            "message"=>"Token not matching client id"
                        ];
                        $rs->getBody()->write(json_encode($error));
                        $rs=$rs->WithStatus(401);
                        $rs = $rs->withHeader('Content-type', 'application/json');
                        return $rs;
                    }
                    //token matching with client id
                    else{
                        return $next($rq,$rs);
                    }
                    //Error when decoding
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
        //No client passed in the body
        return $next($rq,$rs);
    }
}