<?php

namespace AnalogueTest\App;

use Analogue\ORM\EntityMap;

class PopoMap extends EntityMap
{
    protected $attributes = ['name'];

    public function user(Popo $entity)
    {
        return $this->belongsTo($entity, 'AnalogueTest\App\User');
    }
}
