<?php

namespace lbs\catalogue\Controller;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
class SandwichController
{
    public function GetSandwichesByCategorie(Request $rq, Response $rs, $args){
        try{
            $connectionManger = new \MongoDB\Client("mongodb://dbcat");
            $db = $connectionManger->SandwichsDB;
//            $sandwiches = $db->sandwich->find(["categories"=>"chaud"],['categories'=>1]);
            $sandwiches=$db->categorie->aggregate(
                [
                    [
                        '$lookup'=>[
                            'from'=>"sandwich",
                            'localField'=>"nom",
                            'foreignField'=>"categories",
                            'as'=>"sandwiches"
                        ]
                    ],
                    [
                        '$match'=>[
                            "id"=>2
                        ]
                    ]
                ]
            );
            $response = $sandwiches->toArray();
            array_push($response,$rq->getAttribute('route')->getArgument('id'));
            $rs = $rs->withStatus(200)->withHeader('Content-type','application/json');
            $rs->getBody()->write(json_encode($response));
            return $rs;
        }catch(\Exception $e){
            $rs = $rs->withStatus(500)->withHeader('Content-Type','application/json');
            $errorObject = ["type"=>"error","message"=>$e->getMessage()];
            $rs->getBody()->write(json_encode($errorObject));
            return $rs;
        }

    }
}