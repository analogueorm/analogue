<?php namespace AnalogueTest\App;

use Analogue\ORM\ValueObject;

class V extends ValueObject {

    public function __construct($a,$b)
    {
        $this->field_1 = $a;
        $this->field_2 = $b;
    }

}