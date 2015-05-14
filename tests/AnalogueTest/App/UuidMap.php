<?php namespace AnalogueTest\App;

use Analogue\ORM\EntityMap;

class UuidMap extends EntityMap {

    protected $table = 'uuid';

    protected $primaryKey = 'uuid';
}
