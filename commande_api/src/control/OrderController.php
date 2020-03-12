<?php
namespace lbs\command\control;
use Faker\Provider\DateTime;
use Firebase\JWT\JWT;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use lbs\command\model\Order;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\Exception\UnsatisfiedDependencyException;
use lbs\command\model\Client;
use GuzzleHttp\Client as GClient;
class OrderController{
    private $_container;
    private $client;
    public function __construct(\Slim\Container $container=null )
    {
        $this->_container = $container;
        $this->client= new GClient([
            'base_uri'=>'http://api.catalogue.local',
            'timeout'=>2.0
        ]);
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
        //Get the token from the Middleware
        $token = $rq->getAttribute('token');

        if(isset($args["id"])){
            try{
                //Get the order with the id and token passed
                $order = Order::where('id','=',$args['id'])
                                ->where('token','=',$token)
                                ->first();

                //Test if the Order is found
                if($order===null){
                    $error = ["type"=>"error","error"=>404,"message"=>"Order not found   $token"];
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
        //Get origin header from Middleware for Guzzle Client
        $origin = $rq->getAttribute('origin');
        $headers = ['Origin'=>$origin];
        //Get Parsed Body
        $parsedBody = $rq->getParsedBody();
        if(isset($parsedBody['nom']) && isset($parsedBody['mail']) && isset($parsedBody['livraison']) && isset($parsedBody['items'])){
            try {
                //Get the attributes of sandwich for saving the item related to the order
                $items=[];
                $montant=0;
                $body=null;
                foreach ($parsedBody['items'] as $item){
                    $explodedItem=explode("/",$item['uri']);
                    $catalogResponse = $this->client->get('/sandwiches/'.$explodedItem[2],['headers'=>$headers]);
                    if($catalogResponse->getStatusCode()==200){
                        $body = json_decode($catalogResponse->getBody(),true);
                        $i=[
                            'uri'=>$item['uri'],
                            'libelle'=>$body['sandwich']['nom'],
                            'tarif'=>$body['sandwich']['prix']['numberDecimal'],
                            'quantite'=>$item['q']
                        ];
                        array_push($items,$i);
                        $montant+=$i['tarif']*$i['quantite'];
                    }
                }

                //Generate Token for the Order
                $token = openssl_random_pseudo_bytes(32);
                $token = bin2hex($token);

                //Creating the order and saving it
                $order = new Order();
                $order->id=Uuid::uuid4();
                $order->nom=filter_var($parsedBody['nom'],FILTER_SANITIZE_STRING);
                $order->mail=filter_var($parsedBody['mail'],FILTER_SANITIZE_EMAIL);
                $order->livraison=implode(" ",$parsedBody['livraison']);
                $order->created_at=date("Y-m-d H:i:s");
                $order->token=$token;
                $order->montant=$montant;
                $order->saveOrFail();

                //Saving the items
                $order->orderItems()->createMany($items);

                //Update cumul_achat client and update client id in Order
                if(isset($parsedBody['clientId'])){
                    $order = Order::where('token','=',$token)->first();
                    $order->client_id=filter_var($parsedBody['clientId'], FILTER_VALIDATE_INT);
                    $order->saveOrFail();
                    $client = Client::where('id','=',$parsedBody['clientId'])->first();
                    $client->cumul_achats+=$montant;
                    $client->save();
                }


                //Format livraison date
                $fullLivraisonDate = new \DateTime($order->livraison);
                $date = $fullLivraisonDate->format('Y-m-d');
                $time = $fullLivraisonDate->format('H:i:s');

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
                    "montant"=>$order->montant,
                    "items"=>$items

                ];

                $rs=$rs->withStatus(201)
                        ->withHeader('Content-type','application/json')
                        ->withAddedHeader('Location',"/Orders/$order->id");
                $rs->getBody()->write(json_encode($responseObject));

                return $rs;
            }
            catch(\Exception $e){
                $error = ["type"=>"error","error"=>500,"message"=>"Internal Server ".$e->getMessage(),"object"=>$headers];
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

    /*
     * Auth client
     *
     */

    public function AuthClient(Request $rq, Response $rs, $args){
        $key = $this->_container->settings['key'];
        $payload = [
            'iss'=>'http://api.commande.local',
            'aud'=>'http://api.commande.local',
            'iat'=>time(),
            'exp'=>time()+3600,
            'data'=>[
                'clientId'=>$rq->getAttribute('client_id')
            ]
        ];
        $token = JWT::encode($payload,$key);
        $rs=$rs->withStatus(200)->withHeader('Content-type', 'application/json');
        $rs->getBody()->write(json_encode(['token'=>$token]));
        return $rs;
    }

    /*
     * Get Client by id
     *
     */
    public function GetClientById(Request $rq, Response $rs, $args){
        //get token from middleware
        $tokenString = $rq->getAttribute('token');
        $token = json_decode($tokenString,true);
        if($token['data']['clientId']['id']!=$args['id']){
            $error=[
              "type"=>"error",
              "error"=>401,
              "message"=>"Not authorized to get this resource"
            ];
            $rs=$rs->withStatus(401)->withHeader('Content-type', 'application/json');
            $rs->getBody()->write(json_encode($error));
            return $rs;
        }
        try{
            $client = Client::select('id','nom_client','mail_client','cumul_achats')->where('id','=',$token['data']['clientId']['id'])->firstOrFail();
            $response=[
                "type"=>"resource",
                "client"=>$client
            ];
            $rs=$rs->withStatus(200)->withHeader('Content-type', 'application/json');
            $rs->getBody()->write(json_encode($response));
            return $rs;
        }catch (ModelNotFoundException $ex){
            $error=[
                "type"=>"error",
                "error"=>404,
                "message"=>"No client found with the id ".$args['id']
            ];
            $rs=$rs->withStatus(404)->withHeader('Content-type', 'application/json');
            $rs->getBody()->write(json_encode($error));
            return $rs;
        }

    }

    /*
     * Pay order
     *
     */
    public function PayOrder(Request $rq, Response $rs, $args){
        if(isset($args['id'])){
            $order = Order::find($args['id']);
            if($order === null){
                $error=[
                    "type"=>"error",
                    "error"=>404,
                    "message"=>"No Order found with the id ".$args['id']
                ];
                $rs=$rs->withStatus(404)->withHeader('Content-type', 'application/json');
                $rs->getBody()->write(json_encode($error));
                return $rs;
            }
            $body = $rq->getParsedBody();
            if(isset($body['cardNumber']) && isset($body['expireDate'])){
                if(new \DateTime()>new \DateTime($body['expireDate'])){
                    $error=[
                        "type"=>"error",
                        "error"=>422,
                        "message"=>"Your card has expired !!"
                    ];
                    $rs=$rs->withStatus(422)->withHeader('Content-type', 'application/json');
                    $rs->getBody()->write(json_encode($error));
                    return $rs;
                }
                // Generate a ref paiement
                $ref = openssl_random_pseudo_bytes(48);
                $ref = bin2hex($ref);

                //Authenticated paiement
                if(isset($body['remise'])){
                    //TODO : business logic to calculate discount (remise) and deduct from the amount
                    $discount = 0;
                    $order->remise=$discount;
                    Client::where('id','=',$rq->getAttribute('clientId'))->update(['cumul_achats'=>0]);
                }

                //TODO : Update the order state also
                $order->date_paiement=date("Y-m-d H:i:s");
                $order->mode_paiement = 1;
                $order->ref_paiement = $ref;
                $order->save();



                $rs=$rs->withStatus(200)->withHeader('Content-type', 'application/json');
                $rs->getBody()->write(json_encode(["type"=>"resource","order"=>$order]));
                return $rs;
            }
        }
        $error=[
            "type"=>"error",
            "error"=>400,
            "message"=>"Bad Request please verify inputs"
        ];
        $rs=$rs->withStatus(400)->withHeader('Content-type', 'application/json');
        $rs->getBody()->write(json_encode($error));
        return $rs;
    }

    /*
     * Get lient orders
     *
     */
    public function GetClientOrders(Request $rq, Response $rs, $args){
        if(isset($args['id'])){
            $tokenString = $rq->getAttribute('token');
            $decodedToken = json_decode($tokenString,true);
            if($decodedToken['data']['clientId']['id']!=$args['id']){
                $error=[
                    "type"=>"error",
                    "error"=>401,
                    "message"=>"Not authorized to get this resource"
                ];
                $rs=$rs->withStatus(401)->withHeader('Content-type', 'application/json');
                $rs->getBody()->write(json_encode($error));
                return $rs;
            }
            $orders = Order::where('client_id','=',$decodedToken['data']['clientId']['id'])->get();
            if(count($orders)){
                $responseObject=[
                    "type"=>"collection",
                    "count"=>count($orders),
                    "orders"=>$orders
                ];
                $rs=$rs->withStatus(401)->withHeader('Content-type', 'application/json');
                $rs->getBody()->write(json_encode($responseObject));
                return $rs;
            }
            $rs=$rs->withStatus(200)->withHeader('Content-type', 'application/json');
            $rs->getBody()->write(json_encode(["message"=>"No orders found for the client : ".$args['id']]));
            return $rs;
        }
    }

}
