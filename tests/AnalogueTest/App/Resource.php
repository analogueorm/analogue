<?php namespace AnalogueTest\App;

use Analogue\ORM\Entity;

class Resource extends Entity {

    public function __construct($name, V $value = null)
    {
        $this->name = $name;
        // Instantiate value map
        if(! $value)
        {
            $this->value = new V('a','b');
        }
        else $this->value = $value;
    }
}
