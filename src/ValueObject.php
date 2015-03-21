<?php namespace Analogue\ORM;

use ArrayAccess;
use JsonSerializable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Support\Arrayable;

class ValueObject implements Mappable, ArrayAccess, Jsonable, JsonSerializable, Arrayable {
	use MappableTrait;

	/**
	 * Transform the Mappable object to array/json, 
	 * (recursive)
	 * 
	 * @return array
	 */
	public function toArray(array $filters = null)
	{
		$attributes = [];

		foreach($this->attributes as $key => $attribute)
		{
			if ($attribute instanceof Mappable)
			{
				$attributes[$key] = $attribute->toArray();
				continue;
			}
			$attributes[$key] = $attribute;
		}
		return $attributes;
	}

}