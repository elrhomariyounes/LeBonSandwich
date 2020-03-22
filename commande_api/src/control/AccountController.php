<?php


namespace lbs\command\control;
use lbs\command\model\Client;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AccountController
{
    private $container;

    public function __construct($container)
    {
        $this->container = $container;
    }

    /*
     * Client SignUp
     *
     */

    public function SignUp(Request $rq, Response $rs){
        if($rq->getAttribute('has_errors')){
            $errorResponse = [
                "type"=>"error",
                "error"=>422,
                "message"=>$rq->getAttribute('errors')
            ];
            $rs=$rs->withStatus(422)->withHeader("Content-Type","application/json;charset=utf-8");
            $rs->getBody()->write(json_encode($errorResponse));
            return $rs;
        }
        $body = $rq->getParsedBody();
        $client = new Client();
        $client->nom_client=filter_var($body['name'],FILTER_SANITIZE_STRING);
        $client->mail_client=filter_var($body['email'],FILTER_SANITIZE_EMAIL);
        $client->passwd=password_hash($body['password'],PASSWORD_DEFAULT);
        try {
            //TODO : add location header
            $client->save();
            $rs=$rs->withStatus(201)->withHeader("Content-Type","application/json;charset=utf-8");
            $rs->getBody()->write(json_encode($client));
            return $rs;
        }catch (\Exception $ex){
            $error=[
                "type"=>"error",
                "error"=>500,
                "message"=>$ex->getMessage()
            ];
            $rs=$rs->withStatus(500)->withHeader("Content-Type","application/json;charset=utf-8");
            $rs->getBody()->write(json_encode($error));
            return $rs;
        }

    }
}