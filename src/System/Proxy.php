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
	 * Reference to parent entity object
	 *
	 * @var Mappable
	 */
	protected $entity;

	/**
	 * @param string $relation 	relationship method handled by the proxy.
	 */
	public function __construct(Mappable $parentEntity, $relation)
	{
		$this->entity = $parentEntity;
		$this->relation = $relation;
	}

	/**
	 * Call the relationship method on the underlying entity map
	 * 
	 * @return Mappable|EntityCollection
	 */
	public function load()
	{
		return $this->query($this->entity, $this->relation)->getResults($this->relation);
	}

	/**
	 * Return the Query Builder on the relation
	 * 
	 * @param  Mappable  $entity   
	 * @param  string    $relation 
	 * @return Query
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