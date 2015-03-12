<?php namespace Analogue\ORM;

use ArrayAccess;
use JsonSerializable;
use Analogue\ORM\System\ProxyInterface;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Support\Arrayable;

class Entity extends ValueObject implements Mappable, ArrayAccess, Jsonable, JsonSerializable, Arrayable {

	/**
	 * Return the entity's attribute 
	 * @param  string $key 
	 * @return mixed
	 */
	public function getEntityAttribute($key)
	{
		if (! array_key_exists($key, $this->attributes))
		{
			return null;
		}
		if ($this->attributes[$key] instanceof ProxyInterface)
		{
			$this->attributes[$key] = $this->attributes[$key]->load($this,$key);
		}
		return $this->attributes[$key];
	}

	/**
	 * Convert every attributes to value / arrays
	 * 
	 * @return array
	 */
	protected function attributesToArray()
	{	
		$attributes = [];

		foreach($this->attributes as $key => $attribute)
		{
			if ($attribute instanceof ProxyInterface) continue;

			if ($attribute instanceof Entity || $attribute instanceof EntityCollection
			|| $attribute instanceof ValueObject)
			{
				$attributes[$key] = $attribute->toArray();
				continue;
			}
			$attributes[$key] = $attribute;
		}

		return $attributes;
	}

}