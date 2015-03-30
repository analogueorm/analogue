<?php namespace Analogue\ORM\System;

use InvalidArgumentException;
use Analogue\ORM\Mappable;
use Analogue\ORM\EntityMap;
use Analogue\ORM\Commands\Store;
use Analogue\ORM\Commands\Delete;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Collection;
use Analogue\ORM\Commands\Command;
use Analogue\ORM\Drivers\DBAdapter;
use Analogue\ORM\Exceptions\MappingException;

/*
 * The mapper provide all the interactions with the database layer
 * and holds the states for the loaded entity. One instance is 
 * created by used entity class during the application lifecycle.
 */
class Mapper {

	/**
	 * The Manager instance
	 * 
	 * @var \Analogue\ORM\System\Manager
	 */
	protected $manager;

	/**
	 * Instance of EntityMapper Obect
	 * 
	 * @var \Analogue\ORM\EntityMap
	 */
	protected $entityMap;

	/**
	 * The instance of db adapter
	 * 
	 * @var \Analogue\ORM\Drivers\DBAdapter
	 */
	protected $adapter; 


	/**
	 * Event dispatcher instance
	 *
	 * @var \Illuminate\Contracts\Events\Dispatcher
	 */
	protected $dispatcher;

	/**
	 * Entity Cache
	 *
	 * @var  \Analogue\ORM\System\EntityCache 
	 */
	protected $cache;

	/**
	 * Global scopes
	 *
	 * @var array
	 */
	protected $globalScopes = [];

	/**
	 * Custom Commands
	 * 
	 * @var array
	 */
	protected $customCommands = [];

	/**
	 * @param EntityMap 	$entityMapper 
	 * @param DBAdapter     $adapter 
	 * @param Dispatcher 	$dispatcher  
	 * @param Manager  		$manager
	 */
	public function __construct(EntityMap $entityMap, DBAdapter $adapter, Dispatcher $dispatcher, Manager $manager)
	{
		$this->entityMap = $entityMap;

		$this->adapter = $adapter;

		$this->dispatcher = $dispatcher;

		$this->manager = $manager;

		$this->cache = new EntityCache($entityMap);
	}

	/**
	 * Persist an entity or an entity collection into the database
	 * 
	 * @param  Mappable|Collection $entity
	 * @return Mappable|Collection
	 */
	public function store($entity)
	{
		if($this->isArrayOrCollection($entity))
		{
			return $this->storeCollection($entity);
		}
		else if ($this->isMappable($entity)) 
		{
			return $this->storeEntity($entity);
		}
		throw new InvalidArgumentException("Store Command first argument must be an instance of Mappable array, or Collection");
	}

	/**
	 * Check if an object implements the Mappable interface
	 * 
	 * @param  mixed  $item 
	 * @return boolean      
	 */
	protected function isMappable($item)
	{
		return $item instanceof Mappable;
	}

	/**
	 * Return true if an object is an array or collection
	 * 
	 * @param  mixed  $argument 
	 * @return boolean          
	 */
	protected function isArrayOrCollection($argument)
	{
		return ($argument instanceof Collection || is_array($argument)) ? true : false;
	}

	/**
	 * Store a single entity into the database
	 * 
	 * @param  Mappable $entity
	 * @return Entity
	 */
	protected function storeEntity(Mappable $entity)
	{
		$store = new Store($entity, $this, $this->newQueryBuilder() );

		return $store->execute();
	}

	/**
	 * Store an entity collection inside a single DB Transaction
	 * 
	 * @param  Collection|array $entities [description]
	 * @return Collection
	 */
	protected function storeCollection($entities)
	{
		$this->adapter->beginTransaction();

		foreach($entities as $entity)
		{
			if($this->isMappable($entity))
			{
				$this->storeEntity($entity);
			}
			else
			{
				throw new MappingException("Store : Non Mappable Item found into array/collection");	
			}
		}

		$this->adapter->commit();

		return $entities;
	}

	/**
	 * Delete an entity or an entity collection from the database
	 * 
	 * @param  Mappable|Collection 
	 * @return Mappable|Collection
	 */
	public function delete($entity)
	{
		if($this->isArrayOrCollection($entity))
		{
			return $this->deleteCollection($entity);
		} 
		else if ($this->isMappable($entity))
		{
			return $this->deleteEntity($entity);
		}
		throw new InvalidArgumentException("Store Command first argument must be an instance of Mappable array, or Collection");	
	}

	/**
	 * Delete a single entity from the database.
	 * 
	 * @param  Mappable $entity
	 * @return Mappable
	 */
	protected function deleteEntity(Mappable $entity)
	{
		$delete = new Delete($entity, $this, $this->newQueryBuilder() );

		return $delete->execute();
	}

	/**
	 * Delete an Entity Collection inside a single db transaction
	 * 
	 * @param  Collection|array $entities
	 * @return Collection
	 */
	protected function deleteCollection($entities)
	{
		$this->adapter->beginTransaction();

		foreach($entities as $entity)
		{	
			if($this->isMappable($entity))
			{
				$this->deleteEntity($entity);
			}
		}

		$this->adapter->commit();
		
		return $entities;
	}

	/**
	 * Return the entity map for this mapper
	 * 
	 * @return EntityMap
	 */
	public function getEntityMap()
	{
		return $this->entityMap;
	}

	/**
	 * Get the entity cache for the current mapper
	 * 
	 * @return EntityCache 	$entityCache
	 */
	public function getEntityCache()
	{
		return $this->cache;
	}

	/**
	 * Fire the given event for the entity
	 *
	 * @param  string  $event
	 * @param  \Analogue\ORM\Entity  $entity
	 * @param  bool    $halt
	 * @return mixed
	 */
	public function fireEvent($event, $entity, $halt = true)
	{
		$event = "analogue.{$event}.".$this->entityMap->getClass();

		$method = $halt ? 'until' : 'fire';

		return $this->dispatcher->$method($event, $entity);
	}

	/**
	 * Register an entity event with the dispatcher.
	 *
	 * @param  string  $event
	 * @param  \Closure|string  $callback
	 * @return void
	 */
	public function registerEvent($event, $callback)
	{
		$name = $this->entityMap->getClass();

		$this->dispatcher->listen("analogue.{$event}.{$name}", $callback);
	}

	/**
	 * Add a global scope to this mapper query builder
	 * 
	 * @param  ScopeInterface $scope 
	 * @return void                
	 */
	public function addGlobalScope(ScopeInterface $scope)
	{
		$this->globalScopes[get_class($scope)] = $scope;
	}

	/**
	 * Determine if the mapper has a global scope.
	 *
	 * @param  \Analogue\ORM\System\ScopeInterface  $scope
	 * @return bool
	 */
	public function hasGlobalScope($scope)
	{
		return ! is_null($this->getGlobalScope($scope));
	}

	/**
	 * Get a global scope registered with the modal.
	 *
	 * @param  \Analogue\ORM\System\ScopeInterface   $scope
	 * @return \Analogue\ORM\System\ScopeInterface |null
	 */
	public function getGlobalScope($scope)
	{
		return array_first($this->globalScopes, function($key, $value) use ($scope)
		{
			return $scope instanceof $value;
		});
	}

	/**
	 * Get the global scopes for this class instance.
	 *
	 * @return \Analogue\ORM\System\ScopeInterface
	 */
	public function getGlobalScopes()
	{
		return $this->globalScopes;
	}

	/**
	 * Apply all of the global scopes to an Analogue Query builder.
	 *
	 * @param  \Analogue\ORM\System\Query  $builder
	 * @return \Analogue\ORM\System\Query
	 */
	public function applyGlobalScopes($query)
	{
		foreach ($this->getGlobalScopes() as $scope)
		{
			$scope->apply($query, $this);
		}

		return $query;
	}

	/**
	 * Remove all of the global scopes from an Analogue Query builder.
	 *
	 * @param  \Analogue\ORM\System\Query  $builder
	 * @return \Analogue\ORM\System\Query
	 */
	public function removeGlobalScopes($query)
	{
		foreach ($this->getGlobalScopes() as $scope)
		{
			$scope->remove($query, $this);
		}

		return $query;
	}

	/**
	 * Get a new query instance without a given scope.
	 *
	 * @param  \Analogue\ORM\System\ScopeInterface  $scope
	 * @return \Analogue\ORM\System\Query
	 */
	public function newQueryWithoutScope($scope)
	{
		$this->getGlobalScope($scope)->remove($query = $this->getQuery(), $this);

		return $query;
	}

	/**
	 * Get a new query builder that doesn't have any global scopes.
	 *
	 * @return Analogue\ORM\System\Query|static
	 */
	public function newQueryWithoutScopes()
	{
		return $this->removeGlobalScopes($this->getQuery());
	}

	/**
	 * Add a dynamic method that extends the mapper/repository
	 *
	 * @param string $command
	 */
	public function addCustomCommand($command)
	{
		$name = lcfirst(class_basename($command));

		$this->customCommands[$name] = $command;
	}

	/**
	 * Execute a custom command on an Entity
	 * 
	 * @param  string $command 
	 * @param  \Analogue\ORM\Entity|Collection $entity  
	 * @return mixed
	 */
	public function executeCustomCommand($command, $entity)
	{
		$commandClass = $this->customCommands[$command];

		$instance = new $commandClass($entity, $this, $this->newQueryBuilder() );

		return $instance->execute();
	}

	/**
	 * Get all the custom commands regitered on this mapper
	 * 
	 * @return array
	 */
	public function getCustomCommands()
	{
		return array_keys($this->customCommands);
	}

	/**
	 * Check if this mapper supports this command
	 * @param  string  $command 
	 * @return boolean          
	 */
	public function hasCustomCommand($command)
	{
		return in_array($command, $this->getCustomCommands());
	}

	/**
	 * Create a new instance of the mapped entity class
	 * 
	 * @param  array  $attributes 
	 * @return mixed
	 */
	public function newInstance($attributes = array() )
	{
		$class = $this->entityMap->getClass();

		if ($this->entityMap->activator() != null)
		{
			$entity = $this->entityMap->activator();
		}
		else
		{
			$entity = $this->customClassInstance($class);
		}

		// prevent hydrating with an empty array
		if (count($attributes) > 0)
		{
			$entity->setEntityAttributes($attributes);
		}

		return $entity;
	}

	/**
	 * Use a trick to generate a class prototype that we 
	 * can instantiate without calling the constructor.
	 * 
	 * @return mixed
	 */
	protected function customClassInstance($className)
	{
		if(! class_exists($className))
		{
			throw new MappingException("Tried to instantiate a non-existing Entity class : $className");
		}

		$prototype = unserialize(sprintf('O:%d:"%s":0:{}',
			strlen($className),
            			$className
         			)
        		);
		return $prototype;
	}
	
	/**
	 * Get the Analogue Query Builer for this instance
	 * 
	 * @return \Analogue\ORM\System\Query
	 */
	public function getQuery()
	{
		$query = new Query($this, $this->adapter);

		return $this->applyGlobalScopes($query);
	}
	
	/**
	 * Get the Analogue Query Builer for this instance
	 * 
	 * @return \Analogue\ORM\System\Query
	 */
	public function query()
	{
		return $this->getQuery();
	}

	/**
	 * Get an unscoped Analogue Query Builer for this instance
	 * 
	 * @return \Analogue\ORM\System\Query
	 */
	public function globalQuery()
	{
		return $this->newQueryWithoutScopes();
	}

	/**
	 * Get a the Underlying QueryAdapter.
	 *
	 * @return \Analogue\ORM\Drivers\QueryAdapter
	 */
	protected function newQueryBuilder()
	{
		return $this->adapter->getQuery();
	}

	/**
	 * Return the manager instance
	 * 
	 * @return \Analogue\ORM\System\Manager
	 */
	public function getManager()
	{
		return $this->manager;
	}

	/**
	 * Dynamically handle calls to custom commands, or Redirects to query()
	 *
	 * @param  string  $method
	 * @param  array   $parameters
	 * @return mixed
	 */
	public function __call($method, $parameters)
	{
		// Check if method is a custom command on the mapper
		if($this->hasCustomCommand($method))
		{
			if(count($parameters) == 0)
			{
				throw new \Exception("Mapper Command must at least have 1 argument");
			}
			if($parameters[0] instanceof Mappable || $parameters[0] instanceof Collection || is_array($parameters[0]))
			{
				return $this->executeCustomCommand($method, $parameters[0]);	
			}
			else
			{
				throw new \InvalidArgumentException("Mapper Command first argument must be an instance of Mappable or Collection");	
			}
		}

		// Redirect call on a new query instance
		return call_user_func_array(array($this->query(), $method), $parameters);
	}
	
}
