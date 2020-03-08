<?php


namespace lbs\suiviCommande\Model;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    protected $table = 'item';
    protected $primaryKey = 'id';
    protected $fillable= ['uri'];
    public $timestamps = false;
    public $incrementing = true;

    public function order(){
        return $this->belongsTo('lbs\suiviCommande\Model\Order','command_id');
    }
}