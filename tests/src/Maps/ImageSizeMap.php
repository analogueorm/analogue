<?php

namespace TestApp\Maps;

use Analogue\ORM\EntityMap;
use TestApp\Image;
use TestApp\ImageSize;

class ImageSizeMap extends EntityMap
{
	protected $arrayName = null;
	
	protected $primaryKey = null;

	protected $properties = [
		'width',
		'height',
	];
}
