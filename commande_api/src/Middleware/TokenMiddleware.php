<?php


namespace lbs\command\Middleware;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
class TokenMiddleware extends Middleware
{
    public function __invoke(Request $rq, Response $rs, $next)
    {
        //Get the token from Query Param or Header
        $queryToken = $rq->getQueryParams('token',"");
        $headerToken = $rq->getHeader('X-lbs-token');

        if(count($queryToken)!=0){
            $rq = $rq->withAttribute('token',$queryToken);
            return $next($rq,$rs);
        }
        else{
            if(count($headerToken)!=0){
                $rq = $rq->withAttribute('token',$headerToken);
                return $next($rq,$rs);
                }
        }

        //Generate Not Authorized response
        $error = ["type"=>"error","error"=>401,"message"=>"Action Not Authorized, No Token Provided"];
        $rs->getBody()->write(json_encode($error));
        $rs=$rs->WithStatus(401);
        $rs = $rs->withHeader('Content-type', 'application/json');
        return $rs;
    }
}