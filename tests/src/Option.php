<?php

namespace TestApp;

use Analogue\ORM\Entity;

class Option extends Entity
{
    public function __construct($label, $value)
    {
        $this->label = $label;
        $this->value = $value;
    }
}
