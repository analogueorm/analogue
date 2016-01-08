<?php

namespace AnalogueTest\App;

use Analogue\ORM\Entity;

class External extends Entity
{
    public function __construct($name)
    {
        $this->name = $name;
    }
}
