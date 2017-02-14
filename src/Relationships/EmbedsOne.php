<?php

namespace Analogue\ORM\Relationships;

class EmbedsOne extends EmbeddedRelationship
{
	/**
	 * The relation attribute on the parent object
	 * 
	 * @var string
	 */
	protected $relation;

	/**
	 * Transform attributes into embedded object(s), and
	 * match it into the given resultset
	 * 
	 * @param  array $results
	 * @return array
	 */
	public function match(array $results) : array
	{
		return array_map([$this, 'matchSingleResult'], $results);
	}

	/**
	 * Match a single database row's attributes to a single
	 * object, and return the updated attributes
	 * 
	 * @param  array  $attributes
	 * @return array
	 */
	public function matchSingleResult(array $attributes) : array
	{
		return $this->asArray ? $this->matchAsArray($attributes) : $this->matchWithPrefix($attributes);
	}

	protected function matchAsArray($attributes) : array
	{
		$wrapper = $this->getWrapper();
	}

	protected function matchWithPrefix($attributes) : array
	{
		$wrapper = $this->getWrapper();

		//$objectAttributes = 
	}


	/**
	 * Transform embedded object into db column(s)
	 * 
	 * @param  mixed $object 
	 * @return array $columns
	 */
	public function normalize($object) : array
	{

	}
}
