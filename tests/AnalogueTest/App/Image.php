<?php namespace AnalogueTest\App;

use Analogue\ORM\Entity;

class Image extends Entity
{

    public function __construct($path)
    {
        $this->path = $path;
    }

    public function setPath($path)
    {
        $this->path = $path;
    }
}
