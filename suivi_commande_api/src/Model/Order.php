<?php

namespace lbs\suiviCommande\Model;
class Order extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'commande';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $primaryKey = 'id';
    public $timestamps = false;
}