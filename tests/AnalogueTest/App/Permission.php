<?php namespace AnalogueTest\App;

use Analogue\ORM\Entity;

class Permission extends Entity {

    public function __construct($label)
    {
        $this->label = $label;
    }
}
