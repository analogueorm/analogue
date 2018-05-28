<?php

namespace TestApp;

use Analogue\ORM\Entity;

class Settings extends Entity
{
    public function __construct($options = [])
    {
        $this->options = collect($options);
    }
}
