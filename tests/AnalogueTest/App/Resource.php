<?php namespace AnalogueTest\App;

use Analogue\ORM\Entity;

class Resource extends Entity {

    protected $hiddenAttributes = ['value_object_1', 'value_object_2'];

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
