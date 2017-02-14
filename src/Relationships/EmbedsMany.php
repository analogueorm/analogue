<?php

namespace Analogue\ORM\Relationships;

use Analogue\Support\Collection;
use Analogue\ORM\Exceptions\MappingException;

class EmbedsMany extends EmbedsOne
{

	/**
	 * Match a single database row's attributes to a single
	 * object, and return the updated attributes
	 * 
	 * @param  array  $attributes
	 * @return array
	 */
	public function matchSingleResult(array $attributes) : array
	{
		$column = $this->relation;

		if(! $this->asArray) {
			throw new MappingException("column '$column' should be of type array or json");
		}
		
		return $this->matchAsArray($attributes);
	}

	/**
	 * Match array attribute from parent to an embedded object,
	 * and return the updated attributes
	 * 
	 * @param  array $attributes 
	 * @return array             
	 */
	protected function matchAsArray(array $attributes) : array
	{
		// Extract the attributes with the key of the relation,
		// which should be an array.
		$key = $this->relation;

		if(! array_key_exists($key, $attributes) && ! is_array($key)) {
			throw new MappingException("'$key' column should be an array");
		}

		$attributes[$key] = $this->buildEmbeddedCollection($attributes[$key]);

		return $attributes;
	}

	/**
	 * Build an embedded collection and returns it 
	 * 
	 * @param  array $rows 
	 * @return Collection
	 */
	protected function buildEmbeddedCollection($rows) : Collection
	{
		$items = [];

		foreach($rows as $attributes) {
			$items[] = $this->buildEmbeddedObject($attributes);
		}	

		return collect($items);
	}

	/**
	 * Transform embedded object into db column(s)
	 * 
	 * @param  mixed $object 
	 * @return array $columns
	 */
	abstract public function normalize($object) : array
	{

	}
}
