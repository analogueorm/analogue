<?php namespace Analogue\ORM\System;

use Analogue\ORM\Mappable;

abstract class Proxy {

	/**
	 * The name of the relationship method handled by the proxy.
	 * 
	 * @var string
	 */
	protected $relation;

	/**
	 * @param string $relation 	relationship method handled by the proxy.
	 */
	public function __construct($relation)
	{
		$this->relation = $relation;
	}

	/**
	 * Call the relationship method on the underlying entity map
	 * 
	 * @param  Mappable $entity  the entity object
	 * @param  $relation 
	 * @return Mappable|EntityCollection
	 */
	public function load(Mappable $entity)
	{
		$relation = $this->relation;

		return $this->query($entity, $relation)->getResults($relation);
	}

	/**
	 * Return the Query Builder on the relation
	 * 
	 * @param  Mappable  $entity   
	 * @param  string    $relation 
	 * @return Mappable|EntityCollection
	 */
	protected function query(Mappable $entity, $relation)
	{
		$entityMap = $this->getMapper($entity)->getEntityMap();

		return $entityMap->$relation($entity);
	}

	/**
	 * Get the mapper instance for the entity
	 * 
	 * @param  Mappable $entity 
	 * @return \Analogue\ORM\System\Mapper
	 */
	protected function getMapper(Mappable $entity)
	{
		return Manager::mapper($entity);
	}
}