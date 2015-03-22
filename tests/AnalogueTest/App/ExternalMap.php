<?php namespace AnalogueTest\App;

use Analogue\ORM\EntityMap;

class ExternalMap extends EntityMap {

    protected $connection = 'external';

    public function user(External $entity)
    {
        return $this->belongsTo($entity, 'AnalogueTest\App\User');
    }

}
