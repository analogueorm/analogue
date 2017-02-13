<?php

namespace TestApp\Maps;

use Analogue\ORM\EntityMap;
use TestApp\Image;
use TestApp\ImageSize;

class ImageMapCustomPrefix extends ImageMap
{

	public function size(Image $image)
	{
		return $this->embedsOne($image, ImageSize::class)
			->setPrefix('custom');
	}	

}
