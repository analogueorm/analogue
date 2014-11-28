<?php namespace Analogue\ORM\Relationships;

use Analogue\ORM\EntityCollection;

class HasOne extends HasOneOrMany {

	/**
	 * Get the results of the relationship.
	 *
	 * @return mixed
	 */
	public function getResults($relation)
	{
		$result = $this->query->first();

		$this->cacheRelation($result, $relation);

		return $result;
	}

	/**
	 * Get the results of the relationship.
	 *
	 * @return mixed
	 */
	public function fetch()
	{
		return $this->query->first();
	}


	/**
	 * Initialize the relation on a set of entities.
	 *
	 * @param  array   $models
	 * @param  string  $relation
	 * @return array
	 */
	public function initRelation(array $entities, $relation)
	{
		foreach ($entities as $entity)
		{
			$entity->setEntityAttribute($relation, null);
		}

		return $entities;
	}

	/**
	 * Match the eagerly loaded results to their parents.
	 *
	 * @param  array   $models
	 * @param  Analogue\ORM\EntityCollection  $results
	 * @param  string  $relation
	 * @return array
	 */
	public function match(array $entities, EntityCollection $results, $relation)
	{
		return $this->matchOne($entities, $results, $relation);
	}

}
