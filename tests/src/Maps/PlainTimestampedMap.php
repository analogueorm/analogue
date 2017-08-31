<?php

namespace TestApp\Maps;

use Analogue\ORM\EntityMap;

class PlainTimestampedMap extends EntityMap
{
    protected $table = 'timestampeds';

    protected $arrayName = null;

    protected $properties = [
        'id'         => 'id',
        'created_at' => 'created_at',
        'updated_at' => 'updated_at',
    ];

    protected $createdAtColumn = 'created_at';

    protected $updatedAtColumn = 'updated_at';

    public $timestamps = true;
}
