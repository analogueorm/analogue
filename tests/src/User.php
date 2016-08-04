<?php 

namespace TestApp;

use Analogue\ORM\Entity;

class User extends Entity {

    protected $hidden = ['password'];

    public function getEmailAttribute($value)
    {
        return strtolower($value);
    }
}
