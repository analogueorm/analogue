<?php

namespace TestApp\DI;

use Analogue\ORM\Entity;

// Simple object we will inject into Foo
class Bar
{
    public $value = 23;
}

class Foo extends Entity
{
    protected $bar;

    public function __construct(Bar $bar)
    {
        $this->bar = $bar;
    }

    public function getBarValue()
    {
        return $this->bar->value;
    }
}
