<?php

namespace AnalogueTest\App;

use Analogue\ORM\EntityMap;

class ImageMap extends EntityMap
{
    public function imageable(Image $image)
    {
        return $this->morphTo($image);
    }
}
