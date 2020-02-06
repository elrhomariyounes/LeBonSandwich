<?php

namespace lbs\command\model;
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
}