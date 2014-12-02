<?php namespace Analogue\ORM;

use ArrayAccess;
use JsonSerializable;
use Analogue\ORM\System\ProxyInterface;
use Illuminate\Support\Contracts\JsonableInterface;
use Illuminate\Support\Contracts\ArrayableInterface;

class Entity implements ArrayAccess, JsonableInterface, JsonSerializable, ArrayableInterface{

	protected $attributes;

	/**
	 * Set the object attribute raw values (hydration)
	 * 
	 * @param array $attributes 
	 */
	public function setEntityAttributes(array $attributes)
	{
		$this->attributes = $attributes;
	}

	/**
	 * Get the raw object's values.
	 * 
	 * @return array
	 */
	public function getEntityAttributes()
	{
		return $this->attributes;
	}

	/**
	 * Set the raw entity attributes
	 * @param string $key  
	 * @param string $value
	 */
	public function setEntityAttribute($key, $value)
	{
		$this->attributes[$key] = $value;
	}

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
	 * Dynamically retrieve attributes on the entity.
	 *
	 * @param  string  $key
	 * @return mixed
	 */
	public function __get($key)
	{
		return $this->getEntityAttribute($key);
	}

	/**
	 * Dynamically set attributes on the model.
	 *
	 * @param  string  $key
	 * @param  mixed   $value
	 * @return void
	 */
	public function __set($key, $value)
	{
		$this->setEntityAttribute($key, $value);
	}

	/**
	 * Determine if an attribute exists on the model.
	 *
	 * @param  string  $key
	 * @return bool
	 */
	public function __isset($key)
	{
		return array_key_exists($key, $this->attributes); 
	}

	/**
	 * Unset an attribute on the model.
	 *
	 * @param  string  $key
	 * @return void
	 */
	public function __unset($key)
	{
		unset($this->attributes[$key]);
	}

	
	/**
	 * Determine if the given attribute exists.
	 *
	 * @param  mixed  $offset
	 * @return bool
	 */
	public function offsetExists($offset)
	{
		return isset($this->$offset);
	}

	/**
	 * Get the value for a given offset.
	 *
	 * @param  mixed  $offset
	 * @return mixed
	 */
	public function offsetGet($offset)
	{
		return $this->$offset;
	}

	/**
	 * Set the value for a given offset.
	 *
	 * @param  mixed  $offset
	 * @param  mixed  $value
	 * @return void
	 */
	public function offsetSet($offset, $value)
	{
		$this->$offset = $value;
	}

	/**
	 * Unset the value for a given offset.
	 *
	 * @param  mixed  $offset
	 * @return void
	 */
	public function offsetUnset($offset)
	{
		unset($this->$offset);
	}
	
	/**
	 * Convert the object into something JSON serializable.
	 *
	 * @return array
	 */
	public function jsonSerialize()
	{
		return $this->toArray();
	}
	
	/**
	 * Convert the entity instance to JSON.
	 *
	 * @param  int  $options
	 * @return string
	 */
	public function toJson($options = 0)
	{
		return json_encode($this->toArray(), $options);
	}

	/**
	 * Convert the entity instance to an array.
	 *
	 * @return array
	 */
	public function toArray()
	{
		return $this->attributesToArray();
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

			if ($attribute instanceof Entity || $attribute instanceof EntityCollection)
			{
				$attributes[$key] = $attribute->toArray();
				continue;
			}
			$attributes[$key] = $attribute;
		}

		return $attributes;
	}

}