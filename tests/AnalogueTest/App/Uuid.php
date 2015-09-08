<?php namespace AnalogueTest\App;

use Analogue\ORM\Entity;

class Uuid extends Entity {

    public function __construct($uuid, $label)
    {
        $this->uuid = $uuid;
        $this->label = $label;
    }

}