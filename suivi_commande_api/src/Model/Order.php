<?php

namespace lbs\suiviCommande\Model;
class Order extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'commande';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $primaryKey = 'id';
    public $timestamps = false;

    public function orderItems(){
        return $this->hasMany('lbs\suiviCommande\Model\Item','command_id');
    }
}