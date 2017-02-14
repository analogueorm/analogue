<?php

namespace TestApp\Maps;

use Analogue\ORM\EntityMap;
use TestApp\Image;
use TestApp\ImageSize;

class ImageMapArray extends ImageMap
{

	public function size(Image $image)
	{
		return $this->embedsOne(ImageSize::class)
			->asArray();
	}	

}
