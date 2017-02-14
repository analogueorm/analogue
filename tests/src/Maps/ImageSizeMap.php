<?php

namespace TestApp\Maps;

use Analogue\ORM\EntityMap;

class ImageSizeMap extends EntityMap
{
    protected $arrayName = null;

    protected $primaryKey = null;

    protected $properties = [
        'width',
        'height',
    ];
}
