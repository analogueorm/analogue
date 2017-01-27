<?php

namespace TestApp;

use Analogue\ORM\ValueObject;

class Identity extends ValueObject
{
    public function __construct($firstname, $lastname)
    {
        $this->firstname = $firstname;
        $this->lastname = $lastname;
    }
}
