<?php
/**
 * Created by PhpStorm.
 * User: Younes
 * Date: 28/02/2020
 * Time: 19:29
 */

namespace lbs\command\model;


class Client extends \Illuminate\Database\Eloquent\Model
{
    protected $table='client';
    protected $primaryKey='id';
    protected $fillable= ['base_uri'];
    public $timestamps=true;
}