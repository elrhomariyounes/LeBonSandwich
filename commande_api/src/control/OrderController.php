<?php
namespace lbs\command\control;
use lbs\command\model\Order;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\Exception\UnsatisfiedDependencyException;
class OrderController{

    /*
     * Get all the orders
     *
     */
    public function GetOrders(Request $rq, Response $rs, $args)
  {
        try{
            $bddResults = Order::select("id","mail","created_at","montant")->get();
            $commandes = ["type"=>"collection","count"=>count($bddResults),"commandes"=>[]];
            foreach ($bddResults as $commande) {
                array_push($commandes["commandes"],$commande);
            }
            $rs= $rs->withStatus(200);
            $rs = $rs->withHeader('Content-type', 'application/json');
            $rs->getBody()->write(json_encode($commandes));
            return $rs;
        }catch(\Exception $e){
            $error = ["type"=>"error","error"=>500,"message"=>"Internal Server ".$e->getMessage()];
            $rs->getBody()->write(json_encode($error));
            $rs = $rs->withStatus(500);
            $rs = $rs->withHeader('Content-type', 'application/json');
            return $rs;
        }

      $error = ["type"=>"error","error"=>400,"message"=>"Bad Request"];
      $rs->getBody()->write(json_encode($error));
      $rs = $rs->withStatus(400);
      $rs = $rs->withHeader('Content-type', 'application/json');
      return $rs;
  }

  /*
   * Get Order by id
   *
   */
    public function GetOrder(Request $rq, Response $rs, $args){
        //Test if is a POST Method
        if($rq->getMethod()==="POST"){
            $error = ["type"=>"error","error"=>405,"message"=>"Not Allowed Method"];
            $rs->getBody()->write(json_encode($error));
            $rs=$rs->WithStatus(405);
            $rs = $rs->withHeader('Content-type', 'application/json');
            return $rs;
        }
        //Get by Id
        if(isset($args["id"])){
            try{
//          select("id","mail","created_at","montant")->where("id","=",$args["id"])->firstOrFail();
                $bddResults = Order::find($args["id"]);

                //Test if the Order is found
                if($bddResults===null){
                    $error = ["type"=>"error","error"=>404,"message"=>"Order not found"];
                    $rs->getBody()->write(json_encode($error));
                    $rs= $rs->withStatus(404);
                    $rs = $rs->withHeader('Content-type', 'application/json');
                    return $rs;
                }

                $commande = ["type"=>"collection","count"=>1,"commandes"=>[]];
                array_push($commande["commandes"],$bddResults);
                $rs->getBody()->write(json_encode($commande));
                $rs= $rs->withStatus(200);
                $rs = $rs->withHeader('Content-type', 'application/json');
                return $rs;

            }catch(\Exception $e){
                $error = ["type"=>"error","error"=>500,"message"=>"Internal Server ".$e->getMessage()];
                $rs->getBody()->write(json_encode($error));
                $rs = $rs->withStatus(500);
                $rs = $rs->withHeader('Content-type', 'application/json');
                return $rs;
            }
        }

        $error = ["type"=>"error","error"=>400,"message"=>"Bad Request"];
        $rs->getBody()->write(json_encode($error));
        $rs = $rs->withStatus(400);
        $rs = $rs->withHeader('Content-type', 'application/json');
        return $rs;
    }


    public function AddOrder(Request $rq, Response $rs, $args){
        //nom,mail,date_livraison
        $parsedBody = $rq->getParsedBody();
        if(isset($parsedBody['nom']) && isset($parsedBody['mail']) && isset($parsedBody['livraison'])){
            try {
                $order = new Order();
                $order->id=Uuid::uuid4();
                $order->nom=$parsedBody['nom'];
                $order->mail=$parsedBody['mail'];
                $order->livraison=$parsedBody['livraison'];
                $order->created_at=date("Y-m-d H:i:s");
                $order->save();
                $rs->getBody()->write(json_encode($order));
                $rs=$rs->withStatus(201)->withHeader('Content-type','application/json');
                //TODO REDIRECT NOT WORKING
//                $rs=$rs->withRedirect()
                return $rs;
            }
            catch(\Exception $e){
                $error = ["type"=>"error","error"=>500,"message"=>"Internal Server ".$e->getMessage()];
                $rs->getBody()->write(json_encode($error));
                $rs = $rs->withStatus(500);
                $rs = $rs->withHeader('Content-type', 'application/json');
                return $rs;
            }
        }

        $error = ["type"=>"error","error"=>400,"message"=>"Bad Request"];
        $rs->getBody()->write(json_encode($error));
        $rs = $rs->withStatus(400);
        $rs = $rs->withHeader('Content-type', 'application/json');
        return $rs;
    }
}
