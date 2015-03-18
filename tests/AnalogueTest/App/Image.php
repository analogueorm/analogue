<?php namespace AnalogueTest\App;

use Analogue\ORM\Entity;

class Image extends Entity {

    public function __construct($path, $imageableObject)
    {
        $this->path = $path;
        $this->imageable = $imageableObject;
    }
}
