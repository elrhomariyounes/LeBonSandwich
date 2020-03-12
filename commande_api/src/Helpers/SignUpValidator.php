<?php


namespace lbs\command\Helpers;
use Respect\Validation\Validator as v;

class SignUpValidator
{
    public static function Validators(){
        return [
            'name'=>v::stringType()->notEmpty(),
            'email'=>v::email(),
            'password'=>v::notEmpty(),
        ];
    }

}