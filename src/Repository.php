<?php namespace Analogue\ORM;

use Analogue\ORM\Mappable;
use Analogue\ORM\System\Mapper;
use Analogue\ORM\System\Manager;

class Repository {

	protected $mapper;

	/**
	 * @param Mapper|Mappable $mapper 
	 */
	public function __construct($mapper)
	{
		if($mapper instanceof Mappable)
		{
			$this->mapper = Manager::mapper($mapper);
		}
		else if($mapper instanceof Mapper)
		{
			$this->mapper = $mapper;
		}
		else new \InvalidArgumentException('Repository class constuctor need a valid Mapper or Mappable object.');
	}

	/**
	 * Return all Entities from database
	 *  
	 * @return EntityCollection
	 */
	public function all()
	{
		return $this->query()->get();
	}
	
	/**
	 * Fetch a record from the database
	 * @param  integer $id 
	 * @return Entity
	 */
	public function find($id)
	{
		return $this->query()->find($id);
	}

	/**
	 * Get the first Entity  for the given attributes.
	 *
	 * @param  array  $attributes
	 * @return static|null
	 */
	public function firstByAttributes(array $attributes)
	{
		return $this->where($attributes)->first();
	}
	
	/**
	 * Delete an entity or an entity collection from the database
	 * 
	 * @param  Mappable|Collection $entity 
	 * @return Mappable|Collection
	 */
	public function delete($entity)
	{
		return $this->mapper->delete($entity);
	}

	/**
	 * Persist an entity or an entity collection in the database.
	 * 
	 * @param  Mappable|Collection $entity 
	 * @return Mappable|Collection
	 */
	public function store($entity)
	{
		return $this->mapper->store($entity);	
	}

	/**
	 * Get a new query instance on this entity
	 *  
	 * @return \Analogue\ORM\Query 	
	 */
	public function query()
	{
		return $this->mapper->getQuery();
	}

	/**
	 * Dynamically handle calls into the query class.
	 * 
	 * @param  string  $method
	 * @param  array   $parameters
	 * @return mixed
	 */
	public function __call($method, $parameters)
	{
		// Handle call to custom commands
		if (in_array($method, $this->mapper->getCustomCommands() ))
		{	
			return $this->mapper->executeCustomCommand($method, $parameters[0]);
		}

		$result = call_user_func_array(array($this->query(), $method), $parameters);

		return $result;
	}
}