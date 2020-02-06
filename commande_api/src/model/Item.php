<?php


namespace lbs\command\model;


class Item extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'item';
    protected $primaryKey = 'id';
    protected $fillable= ['uri'];
    public $timestamps = false;
}