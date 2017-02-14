<?php

namespace TestApp\Maps;

use Analogue\ORM\EntityMap;
use TestApp\Image;
use TestApp\ImageSize;

class ImageMapCustomMap extends ImageMap
{

	public function size(Image $image)
	{
		return $this->embedsOne(ImageSize::class)
			->setPrefix("")
			->setColumnMap([
				'width' => 'w',
				'height' => 'h',
			]);
	}	

}
