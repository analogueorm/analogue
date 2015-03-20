<?php namespace AnalogueTest\App;

use Analogue\ORM\ValueObject;

class V extends ValueObject {

    public function __construct($a,$b)
    {
        $this->value_object_1 = $a;
        $this->value_object_2 = $b;
    }

}