<?php

namespace TestApp;

use Analogue\ORM\Entity;

class Comment extends Entity
{
    public function __construct($text)
    {
        $this->text = $text;
    }
}
