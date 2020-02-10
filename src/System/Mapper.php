<?php

namespace Analogue\ORM\System;

use Analogue\ORM\Commands\Delete;
use Analogue\ORM\Commands\Store;
use Analogue\ORM\Drivers\DBAdapter;
use Analogue\ORM\EntityMap;
use Analogue\ORM\Exceptions\MappingException;
use Analogue\ORM\Mappable;
use Analogue\ORM\System\Builders\ResultBuilderFactory;
use Analogue\ORM\System\Builders\ResultBuilderInterface;
use Analogue\ORM\System\Cache\AttributeCache;
use Analogue\ORM\System\Cache\InstanceCache;
use ErrorException;
use Illuminate\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Collection;
use InvalidArgumentException;

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
     * The Manager instance.
     *
     * @var \Analogue\ORM\System\Manager
     */
    protected $manager;

    /**
     * Instance of EntityMapper Object.
     *
     * @var \Analogue\ORM\EntityMap
     */
    protected $entityMap;

    /**
     * The instance of db adapter.
     *
     * @var \Analogue\ORM\Drivers\DBAdapter
     */
    protected $adapter;

    /**
     * Event dispatcher instance.
     *
     * @var \Illuminate\Contracts\Events\Dispatcher
     */
    protected $dispatcher;

    /**
     * Entity Cache.
     *
     * @var \Analogue\ORM\System\Cache\AttributeCache
     */
    protected $cache;

    /**
     * Entity instances Object cache.
     *
     * @var \Analogue\ORM\System\Cache\InstanceCache
     */
    protected $instances;

    /**
     * Global scopes.
     *
     * @var array
     */
    protected $globalScopes = [];

    /**
     * Custom Commands.
     *
     * @var array
     */
    protected $customCommands = [];

    /**
     * Available Analogue Events.
     *
     * @var array
     */
    protected $events = [
        'initializing' => \Analogue\ORM\Events\Initializing::class,
        'initialized'  => \Analogue\ORM\Events\Initialized::class,
        'storing'      => \Analogue\ORM\Events\Storing::class,
        'stored'       => \Analogue\ORM\Events\Stored::class,
        'creating'     => \Analogue\ORM\Events\Creating::class,
        'created'      => \Analogue\ORM\Events\Created::class,
        'updating'     => \Analogue\ORM\Events\Updating::class,
        'updated'      => \Analogue\ORM\Events\Updated::class,
        'deleting'     => \Analogue\ORM\Events\Deleting::class,
        'deleted'      => \Analogue\ORM\Events\Deleted::class,
    ];

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

        $this->cache = new AttributeCache($entityMap);
        $this->instances = new InstanceCache($entityMap->getClass());
    }

    /**
     * Map results to a Collection.
     *
     * @param array|Collection $results
     * @param array            $eagerLoads
     * @param bool             $useCache
     *
     * @return Collection
     */
    public function map($results, array $eagerLoads = [], $useCache = false): Collection
    {
        $builder = $this->newResultBuilder(!$useCache);

        if ($results instanceof Collection) {
            // Get underlying collection array
            $results = $results->all();
        }

        if (!is_array($results)) {
            throw new InvalidArgumentException("Parameter 'results' should be an array or a collection.");
        }

        // First, we'll cast every single result to array
        $results = array_map(function ($item) {
            return (array) $item;
        }, $results);

        // Then, we'll pass the results to the Driver's provided transformer, so
        // any DB specific value can be casted before hydration
        $results = $this->adapter->fromDatabase($results);

        $entities = $builder->build($results, $eagerLoads);

        return $this->entityMap->newCollection($entities);
    }

    /**
     * Return result builder used by this mapper.
     *
     * @param bool $skipCache
     *
     * @return ResultBuilderInterface
     */
    protected function newResultBuilder(bool $skipCache = false): ResultBuilderInterface
    {
        $factory = new ResultBuilderFactory();

        return $factory->make($this, $skipCache);
    }

    /**
     * Return all records for a mapped object.
     *
     * @return \Illuminate\Support\Collection
     */
    public function all()
    {
        return $this->query()->get();
    }

    /**
     * Persist an entity or an entity collection into the database.
     *
     * @param Mappable|\Traversable|array $entity
     *
     * @throws \InvalidArgumentException
     * @throws MappingException
     *
     * @return Mappable|\Traversable|array
     */
    public function store($entity)
    {
        if ($this->manager->isTraversable($entity)) {
            return $this->storeCollection($entity);
        } else {
            return $this->storeEntity($entity);
        }
    }

    /**
     * Store an entity collection inside a single DB Transaction.
     *
     * @param \Traversable|array $entities
     *
     * @throws \InvalidArgumentException
     * @throws MappingException
     *
     * @return \Traversable|array
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
     * Store a single entity into the database.
     *
     * @param Mappable $entity
     *
     * @throws \InvalidArgumentException
     * @throws MappingException
     *
     * @return \Analogue\ORM\Entity
     */
    protected function storeEntity($entity)
    {
        $this->checkEntityType($entity);

        $store = new Store($this->aggregate($entity), $this->newQueryBuilder());

        return $store->execute();
    }

    /**
     * Check that the entity correspond to the current mapper.
     *
     * @param mixed $entity
     *
     * @throws InvalidArgumentException
     *
     * @return void
     */
    protected function checkEntityType($entity)
    {
        if (get_class($entity) != $this->entityMap->getClass() && !is_subclass_of($entity, $this->entityMap->getClass())) {
            $expected = $this->entityMap->getClass();
            $actual = get_class($entity);

            throw new InvalidArgumentException("Expected : $expected, got $actual.");
        }
    }

    /**
     * Convert an entity into an aggregate root.
     *
     * @param mixed $entity
     *
     * @throws MappingException
     *
     * @return \Analogue\ORM\System\Aggregate
     */
    protected function aggregate($entity)
    {
        return new Aggregate($entity);
    }

    /**
     * Get a the Underlying Query Builder.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function newQueryBuilder()
    {
        return $this->adapter->getQuery();
    }

    /**
     * Delete an entity or an entity collection from the database.
     *
     * @param  Mappable|\Traversable|array
     *
     * @throws MappingException
     * @throws \InvalidArgumentException
     *
     * @return \Traversable|array
     */
    public function delete($entity)
    {
        if ($this->manager->isTraversable($entity)) {
            return $this->deleteCollection($entity);
        } else {
            $this->deleteEntity($entity);
        }
    }

    /**
     * Delete an Entity Collection inside a single db transaction.
     *
     * @param \Traversable|array $entities
     *
     * @throws \InvalidArgumentException
     * @throws MappingException
     *
     * @return \Traversable|array
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
     * Delete a single entity from the database.
     *
     * @param Mappable $entity
     *
     * @throws \InvalidArgumentException
     * @throws MappingException
     *
     * @return void
     */
    protected function deleteEntity($entity)
    {
        $this->checkEntityType($entity);

        $delete = new Delete($this->aggregate($entity), $this->newQueryBuilder());

        $delete->execute();
    }

    /**
     * Return the entity map for this mapper.
     *
     * @return EntityMap
     */
    public function getEntityMap()
    {
        return $this->entityMap;
    }

    /**
     * Get the entity cache for the current mapper.
     *
     * @return \Analogue\ORM\System\Cache\AttributeCache
     */
    public function getEntityCache(): AttributeCache
    {
        return $this->cache;
    }

    /**
     * Get the instance cache for the current mapper.
     *
     * @return \Analogue\ORM\System\Cache\InstanceCache
     */
    public function getInstanceCache(): InstanceCache
    {
        return $this->instances;
    }

    /**
     * Reset all caches within the mapper.
     *
     * @return void
     */
    public function clearCache()
    {
        $this->cache->clear();
        $this->instances->clear();
    }

    /**
     * Fire the given event for the entity.
     *
     * @param string               $event
     * @param \Analogue\ORM\Entity $entity
     * @param bool                 $halt
     *
     * @throws InvalidArgumentException
     *
     * @return mixed
     */
    public function fireEvent($event, $entity, $halt = true)
    {
        /*if ($entity instanceof Wrapper) {
            throw new InvalidArgumentException('Fired Event with invalid Entity Object');
        }*/

        $eventName = "analogue.{$event}.".$this->entityMap->getClass();

        $method = $halt ? 'until' : 'fire';

        if (!array_key_exists($event, $this->events)) {
            throw new \LogicException("Analogue : Event $event doesn't exist");
        }

        $eventClass = $this->events[$event];
        $event = new $eventClass($entity);

        return $this->dispatcher->$method($eventName, $event);
    }

    /**
     * Register an entity event with the dispatcher.
     *
     * @param string   $event
     * @param \Closure $callback
     *
     * @return void
     */
    public function registerEvent($event, $callback)
    {
        $name = $this->entityMap->getClass();

        $this->dispatcher->listen("analogue.{$event}.{$name}", $callback);
    }

    /**
     * Add a custom object event to the mapper.
     *
     * @param string $name
     * @param string $class
     */
    public function addCustomEvent(string $name, string $class)
    {
        $this->events[$name] = $class;
    }

    /**
     * Add a global scope to this mapper query builder.
     *
     * @param ScopeInterface $scope
     *
     * @return void
     */
    public function addGlobalScope(ScopeInterface $scope)
    {
        $this->globalScopes[get_class($scope)] = $scope;
    }

    /**
     * Determine if the mapper has a global scope.
     *
     * @param \Analogue\ORM\System\ScopeInterface $scope
     *
     * @return bool
     */
    public function hasGlobalScope($scope)
    {
        return !is_null($this->getGlobalScope($scope));
    }

    /**
     * Get a global scope registered with the modal.
     *
     * @param \Analogue\ORM\System\ScopeInterface $scope
     *
     * @return \Analogue\ORM\System\ScopeInterface|null
     */
    public function getGlobalScope($scope)
    {
        return array_first($this->globalScopes, function ($key, $value) use ($scope) {
            return $scope instanceof $value;
        });
    }

    /**
     * Get a new query instance without a given scope.
     *
     * @param \Analogue\ORM\System\ScopeInterface $scope
     *
     * @return \Analogue\ORM\System\Query
     */
    public function newQueryWithoutScope($scope)
    {
        $this->getGlobalScope($scope)->remove($query = $this->getQuery(), $this);

        return $query;
    }

    /**
     * Get the Analogue Query Builder for this instance.
     *
     * @return \Analogue\ORM\System\Query
     */
    public function getQuery()
    {
        $query = new Query($this, $this->adapter);

        return $this->applyGlobalScopes($query);
    }

    /**
     * Apply all of the global scopes to an Analogue Query builder.
     *
     * @param Query $query
     *
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
     * Get the global scopes for this class instance.
     *
     * @return \Analogue\ORM\System\ScopeInterface
     */
    public function getGlobalScopes()
    {
        return $this->globalScopes;
    }

    /**
     * Add a dynamic method that extends the mapper/repository.
     *
     * @param string $command
     */
    public function addCustomCommand($command)
    {
        $name = lcfirst(class_basename($command));

        $this->customCommands[$name] = $command;
    }

    /**
     * Create a new instance of the mapped entity class.
     *
     * @return mixed
     */
    public function newInstance()
    {
        $class = $this->entityMap->getClass();

        if ($this->entityMap->useDependencyInjection()) {
            return $this->newInstanceUsingDependencyInjection($class);
        }

        return $this->newInstanceUsingInstantiator($class);
    }

    /**
     * Return a new object instance using dependency injection.
     *
     * @param string $class
     *
     * @throws ErrorException
     *
     * @return mixed
     */
    protected function newInstanceUsingDependencyInjection($class)
    {
        if (!class_exists(Container::class)) {
            throw new ErrorException("Illuminate\Container\Container is required to use Dependency Injection");
        }

        return Container::getInstance()->make($class);
    }

    /**
     * Return a new object instance using doctrine's instantiator.
     *
     * @param string $class
     *
     * @return mixed
     */
    protected function newInstanceUsingInstantiator($class)
    {
        $instantiator = new \Doctrine\Instantiator\Instantiator();

        return $instantiator->instantiate($class);
    }

    /**
     * Get an unscoped Analogue Query Builder for this instance.
     *
     * @return \Analogue\ORM\System\Query
     */
    public function globalQuery()
    {
        return $this->newQueryWithoutScopes();
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
     * Remove all of the global scopes from an Analogue Query builder.
     *
     * @param Query $query
     *
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
     * Return the manager instance.
     *
     * @return \Analogue\ORM\System\Manager
     */
    public function getManager()
    {
        return $this->manager;
    }

    /**
     * Dynamically handle calls to custom commands, or Redirects to query().
     *
     * @param string $method
     * @param array  $parameters
     *
     * @throws \Exception
     *
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

    /**
     * Check if this mapper supports this command.
     *
     * @param string $command
     *
     * @return bool
     */
    public function hasCustomCommand($command)
    {
        return in_array($command, $this->getCustomCommands());
    }

    /**
     * Get all the custom commands registered on this mapper.
     *
     * @return array
     */
    public function getCustomCommands()
    {
        return array_keys($this->customCommands);
    }

    /**
     * Execute a custom command on an Entity.
     *
     * @param string                 $command
     * @param mixed|Collection|array $entity
     *
     * @throws \InvalidArgumentException
     * @throws MappingException
     *
     * @return mixed
     */
    public function executeCustomCommand($command, $entity)
    {
        $commandClass = $this->customCommands[$command];

        if ($this->manager->isTraversable($entity)) {
            foreach ($entity as $instance) {
                $this->executeSingleCustomCommand($commandClass, $instance);
            }
        } else {
            return $this->executeSingleCustomCommand($commandClass, $entity);
        }
    }

    /**
     * Execute a single command instance.
     *
     * @param string $commandClass
     * @param mixed  $entity
     *
     * @throws \InvalidArgumentException
     * @throws MappingException
     *
     * @return mixed
     */
    protected function executeSingleCustomCommand($commandClass, $entity)
    {
        $this->checkEntityType($entity);

        $instance = new $commandClass($this->aggregate($entity), $this->newQueryBuilder());

        return $instance->execute();
    }

    /**
     * Get the Analogue Query Builder for this instance.
     *
     * @return \Analogue\ORM\System\Query
     */
    public function query()
    {
        return $this->getQuery();
    }
}
