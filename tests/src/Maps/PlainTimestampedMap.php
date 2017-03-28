<?php

namespace TestApp\Maps;

use Analogue\ORM\EntityMap;

class PlainTimestampedMap extends EntityMap
{
	protected $properties = [
		'id' => 'id',
		'created_at' => 'createdAt',
		'updated_at' => 'updatedAt',
	];

	protected $createdAtColumn = 'createdAt';

	protected $updatedAtColumn = 'updatedAt';

    public $timestamps = true;

}
