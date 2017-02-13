<?php

namespace Analogue\ORM\Relationships;

class EmbedsMany extends EmbeddedRelationship
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
	abstract public function normalize($object) : array
	{

	}
}
