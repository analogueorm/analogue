<?php

namespace TestApp\Maps;

use Analogue\ORM\ValueMap;

class IdentityMap extends ValueMap
{
    protected $attributes = [
        'firstname',
        'lastname',
    ];

    protected $mappings = [
        'firstname' => 'fname',
    ];

    protected $arrayName = 'attributes';
}
