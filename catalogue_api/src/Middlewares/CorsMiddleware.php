<?php


namespace lbs\catalogue\Middlewares;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class CorsMiddleware
{
    public function __invoke(Request $rq, Response $rs, $next)
    {
        //No Origin Header
        if(!$this->OriginIsSet($rq)){
            $error=[
                "type"=>"error",
                "error"=>401,
                "message"=>"No Origin Header !!",
                "object"=>$this->OriginIsSet($rq)
            ];
            $rs->getBody()->write(json_encode($error));
            $rs=$rs->WithStatus(401);
            $rs = $rs->withHeader('Content-type', 'application/json');
            return $rs;
        }
        $origin = $rq->getHeaderLine('Origin');
        $response = $next($rq, $rs);
        return $response
            ->withHeader('Access-Control-Allow-Origin', $origin)
            ->withHeader('Access-Control-Allow-Headers', 'X-lbs-token, Content-Type, Accept, Origin, Authorization')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
    }

    private function OriginIsSet(Request $rq){
        return count($rq->getHeader('Origin'))!=0;
    }
}