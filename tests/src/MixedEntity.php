<?php

namespace TestApp;

use Analogue\ORM\MagicGetters;
use Analogue\ORM\MagicSetters;

class MixedEntity
{
    use MagicGetters;
    use MagicSetters;

    protected $property;

    public function setProperty($property)
    {
        $this->property = $property;
    }

    public function getProperty()
    {
        return $this->property;
    }
}
