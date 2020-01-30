<?php

namespace lbs\suiviCommande\Controller;
use Illuminate\Support\Facades\DB;
use lbs\suiviCommande\Model\Order;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
class OrderController
{
    protected $_container;
    public function __construct(\Slim\Container $container){
        $this->_container=$container;
    }
    public function GetOrders(Request $rq, Response $rs, $args){
        //Get the count of the collection
        $count =count(Order::all());
        $state=1;
        $page=1;
        $size=10;
        //Sanitizing the parameters
        if(isset($_GET["page"]))
            $page = intval($_GET["page"]);
        if(isset($_GET["size"]))
            $size=intval($_GET["size"]);
        if(isset($_GET["s"]))
            $state = intval($_GET["s"]);

        //Invalid inputs
        if($page<1 || $page > ($count/$size)+1 || $size<1 || $size>$count){
            $rs = $rs->withStatus(400)->withHeader('Content-type','application/json');
            $rsp = ["type"=>"error","error"=>400,"message"=>"Bad Request Page or Size Invalid !!"];
            $rs->getBody()->write(json_encode($rsp));
            return $rs;
        }
        //Pagination
        $orders=null;
        $orders = Order::select('id','nom','created_at','livraison','status')->orderBy('livraison')->orderBy('created_at');
        if(!is_null($state)){
            $orders=$orders->where('status','=',$state);
        }
        if($page>1){
        $orders = $orders->offset(($page-1)*$size)->limit($size)->get();
        }
        else {
            $orders = $orders->limit($size)->get();
        }

        $ordersObject=[];
        foreach ($orders as $order){
            $self=["self"=>["href"=>"/Order/$order->id"]];
            array_push($ordersObject,["order"=>$order, "links"=>$self]);
        }

        //Returned Object
        $result=[
          "type"=>"collection",
          "count"=>$count,
          "size"=>$size,
          "links"=>[
              "next"=>["href"=>"/Orders/?page=".($page+1)."&size=$size"],
              "prev"=>["href"=>"/Orders/?page=".($page-1)."&size=$size"],
              "last"=>["href"=>"/Orders/?page=".(intval($count/$size)+1)."&size=$size"],
              "first"=>["href"=>"/Orders/?page=1&size=$size"],
          ],
            "orders"=>$orders
        ];

        $rs = $rs->withStatus(200)->withHeader('Content-type','application/json;charset=utf-8');
        $rs->getBody()->write(json_encode($result));
    }
}