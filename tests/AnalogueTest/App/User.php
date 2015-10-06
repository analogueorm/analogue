<?php namespace AnalogueTest\App;

use Analogue\ORM\Entity;

class User extends Entity
{

    public function __construct($email, Role $role)
    {
        $this->email = $email;
        $this->role = $role;
        $this->metas = new Meta;
    }
}
