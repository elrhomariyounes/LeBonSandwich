<?php


namespace lbs\suiviCommande\Model;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    protected $table = 'item';
    protected $primaryKey = 'id';
    public $timestamps = false;
    public $incrementing = true;
}