<?php namespace AnalogueTest\App;

use Analogue\ORM\Entity;

class Resource extends Entity
{

    protected $hidden = ['name', 'v_field_1'];

    public function __construct($name, V $value = null)
    {
        $this->name = $name;
        // Instantiate value map
        if (! $value) {
            $this->value = new V('a', 'b');
        } else {
            $this->value = $value;
        }
    }

    public function setStringAttribute($value)
    {
        $this->attributes['string'] = $value.'_mutated';
    }

    public function getStringAttribute($value)
    {
        return 'mutated_'.$value;
    }
}
