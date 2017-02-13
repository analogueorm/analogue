<?php

namespace Analogue\ORM\Relationships;

class EmbedsOne extends EmbeddedRelationship
{
	/**
	 * Transform attributes into embedded object(s), and
	 * match it into the given resultset
	 * 
	 * @return array
	 */
	public function match(array $results, string $relation) : array
	{

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
