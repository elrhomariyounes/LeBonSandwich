<?php

namespace lbs\catalogue\Controller;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
class CatalogController
{
    private $_container;
    private $_client;
    private $_db;

    public function __construct(\Slim\Container $container = null)
    {
        $this->_container = $container;
        $this->_client= new \MongoDB\Client("mongodb://dbcat");
        $this->_db= $this->_client->SandwichsDB;
    }

    /*
     * Get Sandwiches by categorie
     *
     */
    public function GetSandwichesByCategorie(Request $rq, Response $rs, $args){
        //Get the categorie
        $categorie = $this->_db->categorie->findOne(['id'=>intval($args['id'])],['id'=>1,'nom'=>1]);

        //Check if there is no categorie with this id
        if($categorie==null){
            $rs=$rs->withStatus(404)->withHeader("Content-Type","application/json;charset=utf-8");
            $response = ["type"=>"error","error"=>404,"message"=>"No Categorie found with the id : ".$args['id']];
            $rs->getBody()->write(json_encode($response));
            return $rs;
        }

        //Categorie found, Get the sandwiches with this categorie
        $sandwichesCursor = $this->_db->sandwich->find(['categories'=>$categorie->nom]);
        $sandwiches=$sandwichesCursor->toArray();

        //Check if there is no sandwiches with this categorie
        if(count($sandwiches)==0){
            $rs=$rs->withStatus(404)->withHeader("Content-Type","application/json;charset=utf-8");
            $response = ["type"=>"error","error"=>404,"message"=>"No Sandwiches found with this categorie : ".$categorie->nom];
            $rs->getBody()->write(json_encode($response));
            return $rs;
        }

        //Sandwiches found in this categorie
        else{
            $response = [
                "type"=>"collection",
                "count"=>count($sandwiches),
                "sandwiches"=>$sandwiches
            ];
            $rs=$rs->withStatus(200)->withHeader("Content-Type","application/json;charset=utf-8");
            $rs->getBody()->write(json_encode($response));
            return $rs;
        }

        //Bad Request
        $rs=$rs->withStatus(400)->withHeader("Content-Type","application/json;charset=utf-8");
        $response = ["type"=>"error","error"=>400,"message"=>"Bad Request !! Request not well formed"];
        $rs->getBody()->write(json_encode($response));
        return $rs;
    }

    /*
     * Get Categorie By id
     *
     */
    public function GetCategorieById(Request $rq, Response $rs, $args){
        //Get the categorie
        $categorie = $this->_db->categorie->findOne(['id'=>intval($args['id'])],['_id'=>0]);

        //Check if there is no categorie with this id
        if($categorie==null){
            $rs=$rs->withStatus(404)->withHeader("Content-Type","application/json;charset=utf-8");
            $response = ["type"=>"error","error"=>404,"message"=>"No Categorie found with the id : ".$args['id']];
            $rs->getBody()->write(json_encode($response));
            return $rs;
        }

        //Categorie found
        else{
            $rs=$rs->withStatus(200)->withHeader("Content-Type","application/json;charset=utf-8");
            $response=[
                "type"=>"resource",
                "date"=>date("d-m-Y"),
                "categorie"=>$categorie,
                "links"=>[
                    "sandwiches"=> ["href"=>"/categorie/".$args['id']."/sandwichs"],
                    "self"=>["href"=>"/categorie/".$args['id']]
                ]
            ];
            $rs->getBody()->write(json_encode($response));
            return $rs;
        }

        //Bad Request
        $rs=$rs->withStatus(400)->withHeader("Content-Type","application/json;charset=utf-8");
        $response = ["type"=>"error","error"=>400,"message"=>"Bad Request !! Request not well formed"];
        $rs->getBody()->write(json_encode($response));
        return $rs;
    }

    public function GetSandwichByRef(Request $rq, Response $rs, $args){
        try {
            $sandwich = $this->_db->sandwich->findOne(['ref'=>$args['id']],['_id'=>0]);

            if($sandwich==null){
                $rs=$rs->withStatus(404)->withHeader("Content-Type","application/json;charset=utf-8");
                $response = ["type"=>"error","error"=>404,"message"=>"No Sandwich found with this ref : ".$args['id']];
                $rs->getBody()->write(json_encode($response));
                return $rs;
            }

            $rs=$rs->withStatus(200)->withHeader("Content-Type","application/json;charset=utf-8");
            $responseObject=[
                "type"=>"resource",
                "sandwich"=>$sandwich
            ];
            $rs->getBody()->write(json_encode($responseObject));
            return $rs;

        }catch(\Exception $ex){
            $rs=$rs->withStatus(500)->withHeader("Content-Type","application/json;charset=utf-8");
            $response = ["type"=>"error","error"=>500,"message"=>$ex->getMessage()];
            $rs->getBody()->write(json_encode($response));
            return $rs;
        }

    }
}