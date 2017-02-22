<?php

namespace TestApp;

use Analogue\ORM\Entity;
use Illuminate\Support\Collection;

class User extends Entity
{
    protected $hidden = ['password'];

    public function __construct()
    {
        $this->groups = new Collection();
    }

    public function getEmailAttribute($value)
    {
        return strtolower($value);
    }
}
