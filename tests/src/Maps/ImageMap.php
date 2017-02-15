<?php

namespace TestApp\Maps;

use Analogue\ORM\EntityMap;
use TestApp\Image;
use TestApp\ImageSize;

class ImageMap extends EntityMap
{
    protected $arrayName = null;

    protected $properties = [
        'id',
        'url',
        'size',
    ];

    public function size(Image $image)
    {
        return $this->embedsOne($image, ImageSize::class);
    }
}
