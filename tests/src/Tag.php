<?php

namespace TestApp;

use Analogue\ORM\Entity;

class Tag extends Entity
{
    public function __construct($text)
    {
        $this->text = $text;
    }
}
