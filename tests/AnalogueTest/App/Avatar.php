<?php namespace AnalogueTest\App;

use Analogue\ORM\Entity;

class Avatar extends Entity {

    public function __construct($name, User $user)
    {
        $this->name = $name;
        $this->user = $user;
    }

    public function getPathAttribute()
    {
        return $this->image->path;
    }
}
