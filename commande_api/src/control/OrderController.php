<?php
namespace lbs\command\control;
use lbs\command\model\Order;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\Exception\UnsatisfiedDependencyException;
class OrderController{
    protected $_container;

    public function __construct(\Slim\Container $container=null )
    {
        $this->_container = $container;
    }

    /*
     * Get all the orders
     *
     */
    public function GetOrders(Request $rq, Response $rs, $args)
  {
        try{
            $orders = Order::select("id","mail","created_at","montant")->get();
            $result = ["type"=>"collection","count"=>count($orders),"commandes"=>$orders];
            $rs= $rs->withStatus(200);
            $rs = $rs->withHeader('Content-type', 'application/json');
            $rs->getBody()->write(json_encode($result));
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
        //Test if is a POST Method then Not Allowed
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
                $order = Order::find($args["id"]);

                //Test if the Order is found
                if($order===null){
                    $error = ["type"=>"error","error"=>404,"message"=>"Order not found"];
                    $rs->getBody()->write(json_encode($error));
                    $rs= $rs->withStatus(404);
                    $rs = $rs->withHeader('Content-type', 'application/json');
                    return $rs;
                }

                $result = ["type"=>"resource","count"=>1,"commande"=>$order];
                $rs->getBody()->write(json_encode($result));
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
    /*
     * Method that Add an Order
     *
     */
    public function AddOrder(Request $rq, Response $rs, $args){
        //Get Parsed Body
        $parsedBody = $rq->getParsedBody();
        if(isset($parsedBody['nom']) && isset($parsedBody['mail']) && isset($parsedBody['livraison'])){
            try {
                //Creating the order and saving it
                $order = new Order();
                $order->id=Uuid::uuid4();
                $order->nom=filter_var($parsedBody['nom'],FILTER_SANITIZE_STRING);
                $order->mail=filter_var($parsedBody['mail'],FILTER_SANITIZE_EMAIL);
                $order->livraison=$parsedBody['livraison'];
                $order->created_at=date("Y-m-d H:i:s");
                $order->saveOrFail();

                //Format livraison date
                $fullLivraisonDate = new DateTime($order->livraison);
                $date = $fullLivraisonDate->format('Y-m-d');
                $time = $fullLivraisonDate->format('H:i:s');

                //Generate Token
                $token = openssl_random_pseudo_bytes(32);
                $token = bin2hex($token);

                //Return the response
                $responseObject = [
                    "commande"=>[
                        "nom"=>$order->nom,
                        "mail"=>$order->mail,
                        "livraison"=>[
                            "date"=>$date,
                            "heure"=>$time
                        ]
                    ],
                    "id"=>$order->id,
                    "token"=>$token,
                    "montant"=>$order->montant
                ];

                $rs=$rs->withStatus(201)
                        ->withHeader('Content-type','application/json')
                        ->withAddedHeader('Location',"/Orders/$order->id");
                $rs->getBody()->write(json_encode($responseObject));

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

        $error = ["type"=>"error","error"=>400,"message"=>"Bad Request !!, Please verify the inputs"];
        $rs->getBody()->write(json_encode($error));
        $rs = $rs->withStatus(400);
        $rs = $rs->withHeader('Content-type', 'application/json');
        return $rs;
    }
    /*
     * Method that Update an Order
     *
     */
    public function UpdateOrder(Request $rq, Response $rs, $args){
        if(isset($args["id"])){
            //Get the Order
            $order = Order::find($args["id"]);

            //Return Order Not Found if there is no Order with this id
            if($order===null){
                $error = ["type"=>"error","error"=>404,"message"=>"Order not found"];
                $rs->getBody()->write(json_encode($error));
                $rs= $rs->withStatus(404);
                $rs = $rs->withHeader('Content-type', 'application/json');
                return $rs;
            }

            $parsedBody = $rq->getParsedBody();
            if(isset($parsedBody['nom']) && isset($parsedBody['mail']) && isset($parsedBody['livraison'])){
                try{
                    //Update the Order and Ok Response
                    $order->nom=filter_var($parsedBody['nom'],FILTER_SANITIZE_STRING);
                    $order->mail=filter_var($parsedBody['mail'],FILTER_SANITIZE_EMAIL);
                    $order->livraison=$parsedBody['livraison'];
                    $order->saveOrFail();
                    $rs=$rs->withStatus(200)->withHeader('Content-type','application/json');
                    $rs->getBody()->write(json_encode(["type"=>"collection","count"=>1,"commande"=>$order]));
                    return $rs;
                }catch (\Exception $e){
                    $error = ["type"=>"error","error"=>500,"message"=>"Internal Server ".$e->getMessage()];
                    $rs->getBody()->write(json_encode($error));
                    $rs = $rs->withStatus(500);
                    $rs = $rs->withHeader('Content-type', 'application/json');
                    return $rs;
                }
            }
        }

        $error = ["type"=>"error","error"=>400,"message"=>"Bad Request"];
        $rs->getBody()->write(json_encode($error));
        $rs = $rs->withStatus(400);
        $rs = $rs->withHeader('Content-type', 'application/json');
        return $rs;
    }
}
