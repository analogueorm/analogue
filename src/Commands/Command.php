<?php namespace Analogue\ORM\Commands;

use Carbon\Carbon;
use Analogue\ORM\Entity;
use Analogue\ORM\System\Mapper;
use Analogue\ORM\System\StateChecker;
use Illuminate\Database\Query\Builder as QueryBuilder;

abstract class Command {

	/**
	 * The entity on which the command is executed
	 * 
	 * @var mixed
	 */
	protected $entity;

	/**
	 * Mapper instance
	 * 
	 * @var \Analogue\ORM\System\Mapper
	 */
	protected $mapper;

	/**
	 * Entity Map Instance
	 * 
	 * @var \Analogue\ORM\EntityMap
	 */
	protected $entityMap;

	/**
	 * Entity State Instance
	 * 
	 * @var \Analogue\ORM\System\StateChecker
	 */
	protected $entityState;

	/**
	 * Query Builder instance
	 * 
	 * @var \Illuminate\Database\Query\Builder
	 */
	protected $query;

	public function __construct(Entity $entity, Mapper $mapper, QueryBuilder $query)
	{
		$this->entity = $entity;

		$this->mapper = $mapper;

		$this->entityState = new StateChecker($entity, $mapper);
		
		$this->entityMap = $mapper->getEntityMap();

		$this->query = $query->from($this->entityMap->getTable());
	}

	abstract public function execute();

}