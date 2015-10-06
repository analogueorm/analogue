<?php namespace AnalogueTest\App;

use Analogue\ORM\Entity;
use Analogue\ORM\EntityCollection;

class Role extends Entity
{

    public function __construct($label)
    {
        $this->label = $label;
        $this->permissions = new EntityCollection;
    }
}
