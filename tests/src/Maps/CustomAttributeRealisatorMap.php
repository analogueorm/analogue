<?php

namespace TestApp\Maps;

use Analogue\ORM\EntityMap;

class CustomAttributeRealisatorMap extends EntityMap
{
    protected $table = 'realisators';

    protected $attributes = [
        'name' => 'realisatorName',
    ];
}
