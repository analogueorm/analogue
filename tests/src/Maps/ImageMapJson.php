<?php

namespace TestApp\Maps;

use TestApp\Image;
use TestApp\ImageSize;

class ImageMapJson extends ImageMap
{
    public function size(Image $image)
    {
        return $this->embedsOne($image, ImageSize::class)->asJson();
    }
}
