<?php

namespace Analogue\ORM\System;

use Analogue\ORM\System\Wrappers\Wrapper;
use InvalidArgumentException;
use Analogue\ORM\Mappable;
use Analogue\ORM\EntityMap;
use Analogue\ORM\Commands\Store;
use Analogue\ORM\Commands\Delete;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Collection;
use Analogue\ORM\Drivers\DBAdapter;
use Analogue\ORM\Exceptions\MappingException;

/**
 * The mapper provide all the interactions with the database layer
 * and holds the states for the loaded entity. One instance is
 * created by used entity class during the application lifecycle.
 *
 * @mixin \Analogue\ORM\System\Query
 */
class Mapper
{
    /**
     * The Manager instance
     *
     * @var \Analogue\ORM\System\Manager
     */
    protected $manager;

    /**
     * Instance of EntityMapper Object
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
     * @param EntityMap  $entityMap
     * @param DBAdapter  $adapter
     * @param Dispatcher $dispatcher
     * @param Manager    $manager
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
        if ($this->isArrayOrCollection($entity)) {
            return $this->storeCollection($entity);
        } else {
            return $this->storeEntity($entity);
        }
    }

    /**
     * Return true if an object is an array or collection
     *
     * @param  mixed $argument
     * @return boolean
     */
    protected function isArrayOrCollection($argument)
    {
        return $argument instanceof Collection || is_array($argument);
    }

    /**
     * Store a single entity into the database
     *
     * @param  Mappable $entity
     * @throws \InvalidArgumentException
     * @return \Analogue\ORM\Entity
     */
    protected function storeEntity($entity)
    {
        $this->checkEntityType($entity);

        $store = new Store($this->aggregate($entity), $this->newQueryBuilder());

        return $store->execute();
    }

    /**
     * Convert an entity into an aggregate root
     *
     * @param  mixed $entity
     * @return \Analogue\ORM\System\Aggregate
     */
    protected function aggregate($entity)
    {
         return new Aggregate($entity);
    }

    /**
     * Store an entity collection inside a single DB Transaction
     *
     * @param  Collection|array $entities
     * @throws \InvalidArgumentException
     * @return Collection
     */
    protected function storeCollection($entities)
    {
        $this->adapter->beginTransaction();

        foreach ($entities as $entity) {
            $this->storeEntity($entity);
        }

        $this->adapter->commit();

        return $entities;
    }

    /**
     * Delete an entity or an entity collection from the database
     *
     * @param  mixed|Collection
     * @return Collection|void
     */
    public function delete($entity)
    {
        if ($this->isArrayOrCollection($entity)) {
            return $this->deleteCollection($entity);
        } else {
            $this->deleteEntity($entity);
        }
    }

    /**
     * Delete a single entity from the database.
     *
     * @param  Mappable $entity
     * @throws \InvalidArgumentException
     * @throws MappingException
     * @return void
     */
    protected function deleteEntity($entity)
    {
        $this->checkEntityType($entity);

        $delete = new Delete($this->aggregate($entity), $this->newQueryBuilder());

        $delete->execute();
    }

    /**
     * Delete an Entity Collection inside a single db transaction
     *
     * @param  Collection|array $entities
     * @throws \InvalidArgumentException
     * @throws MappingException
     * @return Collection
     */
    protected function deleteCollection($entities)
    {
        $this->adapter->beginTransaction();

        foreach ($entities as $entity) {
            $this->deleteEntity($entity);
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
     * @return EntityCache  $entityCache
     */
    public function getEntityCache()
    {
        return $this->cache;
    }

    /**
     * Fire the given event for the entity
     *
     * @param  string               $event
     * @param  \Analogue\ORM\Entity $entity
     * @param  bool                 $halt
     * @throws InvalidArgumentException
     * @return mixed
     */
    public function fireEvent($event, $entity, $halt = true)
    {
        if ($entity instanceof Wrapper) {
            throw new InvalidArgumentException('Fired Event with invalid Entity Object');
        }

        $event = "analogue.{$event}." . $this->entityMap->getClass();

        $method = $halt ? 'until' : 'fire';

        return $this->dispatcher->$method($event, $entity);
    }

    /**
     * Register an entity event with the dispatcher.
     *
     * @param  string   $event
     * @param  \Closure $callback
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
     * @param  \Analogue\ORM\System\ScopeInterface $scope
     * @return bool
     */
    public function hasGlobalScope($scope)
    {
        return !is_null($this->getGlobalScope($scope));
    }

    /**
     * Get a global scope registered with the modal.
     *
     * @param  \Analogue\ORM\System\ScopeInterface $scope
     * @return \Analogue\ORM\System\ScopeInterface|null
     */
    public function getGlobalScope($scope)
    {
        return array_first($this->globalScopes, function($key, $value) use ($scope) {
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
     * @param Query $query
     * @return \Analogue\ORM\System\Query
     */
    public function applyGlobalScopes($query)
    {
        foreach ($this->getGlobalScopes() as $scope) {
            $scope->apply($query, $this);
        }

        return $query;
    }

    /**
     * Remove all of the global scopes from an Analogue Query builder.
     *
     * @param Query $query
     * @return \Analogue\ORM\System\Query
     */
    public function removeGlobalScopes($query)
    {
        foreach ($this->getGlobalScopes() as $scope) {
            $scope->remove($query, $this);
        }

        return $query;
    }

    /**
     * Get a new query instance without a given scope.
     *
     * @param  \Analogue\ORM\System\ScopeInterface $scope
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
     * @return Query
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
     * @param  string                 $command
     * @param  mixed|Collection|array $entity
     * @throws \InvalidArgumentException
     * @return mixed
     */
    public function executeCustomCommand($command, $entity)
    {
        $commandClass = $this->customCommands[$command];

        if ($this->isArrayOrCollection($entity)) {
            foreach ($entity as $instance) {
                $this->executeSingleCustomCommand($commandClass, $instance);
            }
        } else {
            return $this->executeSingleCustomCommand($commandClass, $entity);
        }
    }

    /**
     * Execute a single command instance
     *
     * @param  string $commandClass
     * @param  mixed  $entity
     * @throws \InvalidArgumentException
     * @return mixed
     */
    protected function executeSingleCustomCommand($commandClass, $entity)
    {
        $this->checkEntityType($entity);

        $instance = new $commandClass($this->aggregate($entity), $this->newQueryBuilder());

        return $instance->execute();
    }

    /**
     * Check that the entity correspond to the current mapper.
     *
     * @param  mixed $entity
     * @throws InvalidArgumentException
     * @return void
     */
    protected function checkEntityType($entity)
    {
        if (get_class($entity) != $this->entityMap->getClass()) {
            $expected = $this->entityMap->getClass();
            $actual = get_class($entity);
            throw new InvalidArgumentException("Expected : $expected, got $actual.");
        }
    }

    /**
     * Get all the custom commands registered on this mapper
     *
     * @return array
     */
    public function getCustomCommands()
    {
        return array_keys($this->customCommands);
    }

    /**
     * Check if this mapper supports this command
     * @param  string $command
     * @return boolean
     */
    public function hasCustomCommand($command)
    {
        return in_array($command, $this->getCustomCommands());
    }

    /**
     * Create a new instance of the mapped entity class
     *
     * @param  array $attributes
     * @return mixed
     */
    public function newInstance($attributes = [])
    {
        $class = $this->entityMap->getClass();

        if ($this->entityMap->activator() != null) {
            $entity = $this->entityMap->activator();
        } else {
            $entity = $this->customClassInstance($class);
        }

        // prevent hydrating with an empty array
        if (count($attributes) > 0) {
            $entity->setEntityAttributes($attributes);
        }

        return $entity;
    }

    /**
     * Use a trick to generate a class prototype that we
     * can instantiate without calling the constructor.
     *
     * @param string|null $className
     * @throws MappingException
     * @return mixed
     */
    protected function customClassInstance($className)
    {
        if (!class_exists($className)) {
            throw new MappingException("Tried to instantiate a non-existing Entity class : $className");
        }

        $prototype = unserialize(sprintf('O:%d:"%s":0:{}', strlen($className), $className));
        return $prototype;
    }
    
    /**
     * Get the Analogue Query Builder for this instance
     *
     * @return \Analogue\ORM\System\Query
     */
    public function getQuery()
    {
        $query = new Query($this, $this->adapter);

        return $this->applyGlobalScopes($query);
    }
    
    /**
     * Get the Analogue Query Builder for this instance
     *
     * @return \Analogue\ORM\System\Query
     */
    public function query()
    {
        return $this->getQuery();
    }

    /**
     * Get an unscoped Analogue Query Builder for this instance
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
    public function newQueryBuilder()
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
     * @param  string $method
     * @param  array  $parameters
     * @throws \Exception
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        // Check if method is a custom command on the mapper
        if ($this->hasCustomCommand($method)) {
            if (count($parameters) == 0) {
                throw new \Exception("$method must at least have 1 argument");
            }
            return $this->executeCustomCommand($method, $parameters[0]);
        }

        // Redirect call on a new query instance
        return call_user_func_array([$this->query(), $method], $parameters);
    }
}
