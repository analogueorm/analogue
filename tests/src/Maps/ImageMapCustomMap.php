<?php

namespace TestApp\Maps;

use TestApp\Image;
use TestApp\ImageSize;

class ImageMapCustomMap extends ImageMap
{
    public function size(Image $image)
    {
        return $this->embedsOne($image, ImageSize::class)
            ->setPrefix('')
            ->setColumnMap([
                'width'  => 'w',
                'height' => 'h',
            ]);
    }
}
