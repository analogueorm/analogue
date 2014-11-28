<?php namespace Analogue\ORM\System;

abstract class Proxy {

	/**
	 * Call the relationship method on the underlying entity map
	 * 
	 * @param  [type] $entity   [description]
	 * @param  [type] $relation [description]
	 * @return [type]           [description]
	 */
	public function load($entity, $relation)
	{
		return $this->query($entity, $relation)->getResults($relation);
	}

	/**
	 * Return the Query Builder on the relation
	 * 
	 * @param  [type] $entity   [description]
	 * @param  [type] $relation [description]
	 * @return [type]           [description]
	 */
	protected function query($entity, $relation)
	{
		$entityMap = $this->getMapper($entity)->getEntityMap();

		return $entityMap->$relation($entity);
	}

	/**
	 * Get the mapper instance for the entity
	 * 
	 * @param  $entity ]
	 * @return \Analogue\ORM\System\Mapper
	 */
	protected function getMapper($entity)
	{
		return Manager::mapper($entity);
	}
}