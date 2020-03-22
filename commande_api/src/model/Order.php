<?php

namespace lbs\command\model;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema()
 *
 *
 */
class Order extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'commande';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $primaryKey = 'id';
    public $timestamps = false;

    public function orderItems(){
        return $this->hasMany('lbs\command\model\Item','command_id');
    }

    /**
     * @OA\Property(type="integer")
     * @var int
     *
     */
    public $id;

    /**
     * @OA\Property(type="string")
     * @var string
     */
    public $name;
}