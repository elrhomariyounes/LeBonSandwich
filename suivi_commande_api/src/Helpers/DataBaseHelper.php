<?php
namespace lbs\suiviCommande\Helpers;
class DataBaseHelper
{
    public static function ConnectToDatabase($path) {
        $config = parse_ini_file($path);
        $db = new \Illuminate\Database\Capsule\Manager();
        $db->addConnection($config);
        $db->setAsGlobal();
        $db->bootEloquent();
    }
}