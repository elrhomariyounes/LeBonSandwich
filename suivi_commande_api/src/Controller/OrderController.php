<?php

namespace lbs\suiviCommande\Controller;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use lbs\suiviCommande\Model\Item;
use lbs\suiviCommande\Model\Order;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
class OrderController
{
    private $_container;
    public function __construct(\Slim\Container $container){
        $this->_container=$container;
    }
    /*
     * Get all Orders with pagination page & size parameters
     *
     */
    public function GetOrders(Request $rq, Response $rs, $args){
        //Get the count of the collection
        $count =count(Order::all());
        $state=1;
        $page=1;
        $size=10;
        //
        if(isset($_GET["page"]))
            $page = intval($_GET["page"]);
        if(isset($_GET["size"]))
            $size=intval($_GET["size"]);
        if(isset($_GET["s"]))
            $state = intval($_GET["s"]);

        //Invalid inputs
        if($page<1 || $page > ($count/$size)+1 || $size<10 || $size>$count){
            $rs = $rs->withStatus(400)->withHeader('Content-Type','application/json;charset=utf-8');
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
        $orders = $orders->offset(($page-1)*$size)->with('orderItems')->limit($size)->get();
        }
        else {
            $orders = $orders->limit($size)->with('orderItems')->get();
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

        //Unset next and prev field
        $last = intval($count/$size)+1;
        if($page==$last){
            unset($result['links']['next']);
            unset($result['links']['last']);
        }

        if($page==1){
            unset($result['links']['prev']);
            unset($result['links']['first']);
        }


        $rs = $rs->withStatus(200)->withHeader('Content-Type','application/json;charset=utf-8');
        $rs->getBody()->write(json_encode($result));
    }

    /*
     * Get Order by ID
     *
     */
    public function GetOrderById(Request $rq, Response $rs, $args){
        try {
            //Get the order by id
            $order = Order::query()->select('id', 'created_at', 'livraison', 'nom', 'mail', "montant")->where('id', '=', $args['id'])->firstOrFail();
            //Set the items of the order
            $order->items = Item::query()->select('uri','libelle','tarif','quantite')->where('command_id', '=', $args['id'])->get();

            $rs = $rs->withStatus(200)->withHeader('Content-Type', 'application/json;charset=utf-8');
            $rs->getBody()->write(json_encode([
                "type" => "ressource",
                "links" => ["self"=>"/orders/".$args['id'],"items"=>"/orders/" . $args['id']."/items"],
                "order" => $order
            ]));
            //Order Not Found
        } catch (ModelNotFoundException $exception) {
            $rs = $rs->withStatus(404)->withHeader('Content-Type', 'application/json;charset=utf-8');
            $rs->getBody()->write(json_encode([
                "type" => "error",
                "error" => 404,
                "message" => "Order Not Found !! : " . $rq->getUri()
            ]));
        }

        return $rs;
    }

    /*
     * Update Order Status
     *
     */
    public function UpdateOrderStatus(Request $rq, Response $rs, $args){
        // Valid States
        $validStates = [1,2,3,4];

        //Get body
        $body = $rq->getParsedBody();
        if(isset($body['state']) && $args['id']){
            if(!in_array($body['state'],$validStates)){
                $error = [
                    "type"=>"error",
                    "error"=>422,
                    "message"=>"Invalid state value !!"
                ];
                $rs=$rs->withStatus(422)->withHeader('Content-type', 'application/json');
                $rs->getBody()->write(json_encode($error));
                return $rs;
            }
            try {
                //Update the order
                $order = Order::where('id','=',$args['id'])->firstOrFail();
                $order->status = filter_var($body['state'],FILTER_VALIDATE_INT);
                $order->save();
                //Response object
                $responseObject = [
                  "type"=>"resource",
                    "order"=>$order
                ];
                $rs=$rs->withStatus(200)->withHeader('Content-type', 'application/json');
                $rs->getBody()->write(json_encode($responseObject));
                return $rs;
            }
            catch (ModelNotFoundException $ex){
                $error = [
                    "type"=>"error",
                    "error"=>404,
                    "message"=>"Order not found with the id : ".$args['id']
                ];
                $rs=$rs->withStatus(404)->withHeader('Content-Type', 'application/json');
                $rs->getBody()->write(json_encode($error));
                return $rs;
            }
        }
    }

    /*
     * Get Order items
     *
     */
    public function GetOrderItems(Request $rq, Response $rs, $args){
        if(isset($args['id'])){
            $items = Item::query()->select('uri','libelle','tarif','quantite')->where('command_id', '=', $args['id'])->get();
            $order = Order::find($args['id']);
            if(count($items)){
                $responseObject=[
                    "type"=>"collection",
                    "count"=>count($items),
                    "items"=>$items
                ];
                $rs=$rs->withStatus(200)->withHeader("Content-Type","application/json");
                $rs->getBody()->write(json_encode($responseObject));
                return $rs;
            }
            $error=[
                "type"=>"error",
                "error"=>404,
                "message"=>"Order not found with the id : ".$args['id']
            ];
            $rs=$rs->withStatus(404)->withHeader("Content-Type","application/json");
            $rs->getBody()->write(json_encode($error));
            return $rs;
        }
    }
}