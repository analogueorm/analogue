<?php namespace Analogue\ORM;

use ArrayAccess;
use JsonSerializable;
use Analogue\ORM\System\EntityProxy;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Support\Arrayable;

class Entity extends ValueObject implements Mappable, ArrayAccess, Jsonable, JsonSerializable, Arrayable {

	/**
	 * Return the entity's attribute 
	 * @param  string $key 
	 * @return mixed
	 */
	public function __get($key)
	{
		if (! array_key_exists($key, $this->attributes))
		{
			return null;
		}
		if ($this->attributes[$key] instanceof EntityProxy)
		{
			$this->attributes[$key] = $this->attributes[$key]->load();
		}
		return $this->attributes[$key];
	}

	/**
	 * Convert every attributes to value / arrays
	 * 
	 * @return array
	 */
	public function toArray()
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

		return parent::toArray($attributes);
	}

}