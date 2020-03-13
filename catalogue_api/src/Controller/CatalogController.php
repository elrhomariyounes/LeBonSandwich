<?php

namespace lbs\catalogue\Controller;
use MongoDB\Exception\Exception;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
class CatalogController
{
    private $_container;
    private $_client;
    private $_db;

    public function __construct(\Slim\Container $container)
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
        $category = $this->_db->categorie->findOne(['id'=>intval($args['id'])],['id'=>1,'nom'=>1]);

        //Check if there is no categorie with this id
        if($category==null){
            $rs=$rs->withStatus(404)->withHeader("Content-Type","application/json;charset=utf-8");
            $response = ["type"=>"error","error"=>404,"message"=>"No Categorie found with the id : ".$args['id']];
            $rs->getBody()->write(json_encode($response));
            return $rs;
        }

        //Categorie found, Get the sandwiches with this categorie
        $sandwichesCursor = $this->_db->sandwich->find(['categories'=>$category->nom]);
        $sandwiches=$sandwichesCursor->toArray();

        //Check if there is no sandwiches with this categorie
        if(count($sandwiches)==0){
            $rs=$rs->withStatus(404)->withHeader("Content-Type","application/json;charset=utf-8");
            $response = ["type"=>"error","error"=>404,"message"=>"No Sandwiches found in this categorie : ".$category->nom];
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
     * Get all categories
     *
     */
    public function GetAllCategories(Request $rq, Response $rs,$args){
        $categoriesCursor = $this->_db->categorie->find([],['_id'=>0]);
        $categories = $categoriesCursor->toArray();
        $rs = $rs->withStatus(200)->withHeader("Content-Type","application/json;charset=utf-8");
        $responseObject = [
            "type"=>"collection",
            "count"=>count($categories),
            "categories"=>$categories
        ];
        $rs->getBody()->write(json_encode($responseObject));
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
                    "sandwiches"=> ["href"=>"/categories/".$args['id']."/sandwiches"],
                    "self"=>["href"=>"/categories/".$args['id']]
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

    /*
     * Get all the sandwiches
     *
     */
    public function GetAllSandwiches(Request $rq, Response $rs, $args){
        $sandiwchesCursor = $this->_db->sandwich->find([],['_id'=>0]);
        $sandwiches = $sandiwchesCursor->toArray();
        $rs = $rs->withStatus(200)->withHeader("Content-Type","application/json;charset=utf-8");
        $responseObject = [
            "type"=>"collection",
            "count"=>count($sandwiches),
            "sandwiches"=>$sandwiches
        ];
        $rs->getBody()->write(json_encode($responseObject));
        return $rs;
    }
    /*
     * Get sandwich by ref
     *
     */
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

    /*
     * Delete a sandwich
     *
     */
    public function DeleteSandwich(Request $rq, Response $rs, $args){
        if(isset($args['id'])){
            $sandwich = $this->_db->sandwich->findOne(['ref'=>$args['id']]);
            if($sandwich==null){
                $rs=$rs->withStatus(404)->withHeader("Content-Type","application/json;charset=utf-8");
                $response = ["type"=>"error","error"=>404,"message"=>"No Sandwich found with this ref : ".$args['id']];
                $rs->getBody()->write(json_encode($response));
                return $rs;
            }
            try {
                $this->_db->sandwich->deleteOne(['ref'=>$args['id']]);
                $rs = $rs->withStatus(204)->withHeader("Content-Type","application/json;charset=utf-8");
                return $rs;
            }
            catch (Exception $ex){
                $rs=$rs->withStatus(500)->withHeader("Content-Type","application/json;charset=utf-8");
                $response = ["type"=>"error","error"=>500,"message"=>$ex->getMessage()];
                $rs->getBody()->write(json_encode($response));
                return $rs;
            }

        }
    }
}