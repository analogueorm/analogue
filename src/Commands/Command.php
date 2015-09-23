<?php namespace Analogue\ORM\Commands;

use Carbon\Carbon;
use Analogue\ORM\System\Mapper;
use Analogue\ORM\System\StateChecker;
use Analogue\ORM\Drivers\QueryAdapter;
use Analogue\ORM\System\Wrappers\Factory;

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

	/**
	 * Entity Wrapper factory 
	 * 
	 * @var \Analogue\ORM\System\Wrappers\Factory
	 */
	protected $wrapperFactory;


	public function __construct($entity, Mapper $mapper, QueryAdapter $query)
	{
		$this->wrapperFactory = new Factory;

		$this->entity = $this->wrapperFactory->make($entity);

		$this->mapper = $mapper;

		$this->entityState = new StateChecker($this->entity, $mapper);
		
		$this->entityMap = $mapper->getEntityMap();

		$this->query = $query->from($this->entityMap->getTable());
	}

	abstract public function execute();

}
