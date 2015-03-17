<?php namespace Analogue\ORM\System;

use Analogue\ORM\Mappable;
use Analogue\ORM\EntityMap;
use Analogue\ORM\Commands\Store;
use Analogue\ORM\Commands\Delete;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Collection;
use Analogue\ORM\Commands\Command;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Analogue\ORM\Exceptions\MappingException;

/*
 * The mapper provide all the interactions with the database layer
 * and holds the states for the loaded entity. One instance is 
 * created by used entity class during the application lifecycle.
 */
class Mapper {

	/**
	 * Instance of EntityMapper Obect
	 * 
	 * @var \Analogue\ORM\EntityMap
	 */
	protected $entityMap;

	/**
	 * The Database Connection
	 * 
	 * @var \Illuminate\Database\Connection
	 */
	protected $connection;

	/**
	 * Event dispatcher instance
	 *
	 * @var \Illuminate\Events\Dispatcher
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
	 * @param EntityMap $entityMapper 
	 * @param ConnectionInterace    $connection   
	 */
	public function __construct(EntityMap $entityMap, Connection $connection, Dispatcher $dispatcher)
	{
		$this->entityMap = $entityMap;

		$this->connection = $connection;

		$this->dispatcher = $dispatcher;

		$this->entityMap->setDateFormat($connection->getQueryGrammar()->getDateFormat());

		$this->cache = new EntityCache($entityMap);

		// Fire Initializing Event
		$this->fireEvent('initializing', $this);

		$mapInitializer = new MapInitializer($this);
		
		$mapInitializer->init();

		// Fire Initialized Event
		$this->fireEvent('initialized', $this);
	
	}

	/**
	 * Persist an entity or an entity collection into the database
	 * 
	 * @param  Mappable|Collection $entity
	 * @return Mappable|Collection
	 */
	public function store($entity)
	{
		if($entity instanceof Collection)
		{
			return $this->storeCollection($entity);
		}
		else return $this->storeEntity($entity);
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
	 * @param  Collection $entities [description]
	 * @return Collection
	 */
	protected function storeCollection(Collection $entities)
	{
		$thid->connection->beginTransaction();

		foreach($entities as $entity)
		{
			$this->storeEntity($entity);
		}

		$thid->connection->commit();

		return $entities;
	}

	/**
	 * Delete an entity or an entity collection from the database
	 * 
	 * @param  Mappable|Collection 
	 * @return Mappable|Collection
	 */
	public function delete(Mappable $entity)
	{
		if($entity instanceof Collection)
		{
			return $this->deleteCollection($entity);
		}
		else return $this->deleteEntity($entity);
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
	 * @param  Collection $entities
	 * @return Collection
	 */
	protected function deleteCollection(Collection $entities)
	{
		$thid->connection->beginTransaction();

		foreach($entities as $entity)
		{
			$this->deleteEntity($entity);
		}

		$thid->connection->commit();
		
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
	 * @return [type] [description]
	 */
	public function getEntityCache()
	{
		return $this->cache;
	}

	/**
	 * [getConnection description]
	 * @return [type] [description]
	 */
	public function getConnection()
	{
		return $this->connection;
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
	public static function hasGlobalScope($scope)
	{
		return ! is_null($this->getGlobalScope($scope));
	}

	/**
	 * Get a global scope registered with the modal.
	 *
	 * @param  \Analogue\ORM\System\ScopeInterface   $scope
	 * @return \Analogue\ORM\System\ScopeInterface |null
	 */
	public static function getGlobalScope($scope)
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
	 * @param  \Analogue\ORM\Entity $entity  
	 * @return mixed
	 */
	public function executeCustomCommand($command, Mappable $entity)
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
	 * Create a new instance of the mapped entity class
	 * 
	 * @param  array  $attributes 
	 * @return mixed
	 */
	public function newInstance($attributes = array() )
	{
		$class = $this->entityMap->getClass();

		// If an activator method is present
		// on the entity map, use it to 
		// instantiate the entity.
		if ($this->entityMap->hasActivator() )
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
		$query = new Query($this->newQueryBuilder(), $this);

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
	 * Get a new Illuminate QueryBuilder instance for the current connection.
	 *
	 * @return \Illuminate\Database\Query\Builder
	 */
	protected function newQueryBuilder()
	{
		$connection = $this->getConnection();

		$grammar = $connection->getQueryGrammar();

		return new QueryBuilder($connection, $grammar, $connection->getPostProcessor() );
	}
}