<?php

namespace Analogue\ORM\System;

use Exception;
use Analogue\ORM\EntityMap;
use Analogue\ORM\Repository;
use Analogue\ORM\System\Wrappers\Wrapper;
use Illuminate\Contracts\Events\Dispatcher;
use Analogue\ORM\Exceptions\MappingException;
use Analogue\ORM\Drivers\Manager as DriverManager;

/**
 * This class keeps track of instantiated mappers, and entity <-> entityMap associations
 */
class Manager
{
    /**
     * Manager instance
     *
     * @var Manager
     */
    protected static $instance;

    /**
     * Driver Manager
     *
     * @var \Analogue\ORM\Drivers\Manager
     */
    protected $drivers;

    /**
     * Registered entity classes and corresponding map objects.
     *
     * @var array
     */
    protected $entityClasses = [];

    /**
     * Key value store of ValueObject Classes and corresponding map classes
     *
     * @var array
     */
    protected $valueClasses = [];

    /**
     * Morph map
     */
    protected $morphMap = [];

    /**
     * Loaded Mappers
     *
     * @var array
     */
    protected $mappers = [];

    /**
     * Loaded Repositories
     *
     * @var array
     */
    protected $repositories = [];

    /**
     * Event dispatcher instance
     *
     * @var \Illuminate\Contracts\Events\Dispatcher
     */
    protected $eventDispatcher;

    /**
     * Available Analogue Events
     *
     * @var array
     */
    protected $events = [
        'initializing',
        'initialized',
        'store',
        'stored',
        'creating',
        'created',
        'updating',
        'updated',
        'deleting',
        'deleted',
    ];

    /**
     * @param \Analogue\ORM\Drivers\Manager $driverManager
     * @param Dispatcher                    $event
     */
    public function __construct(DriverManager $driverManager, Dispatcher $event)
    {
        $this->drivers = $driverManager;

        $this->eventDispatcher = $event;

        static::$instance = $this;
    }

    /**
     * Create a mapper for a given entity (static alias)
     *
     * @param  \Analogue\ORM\Mappable|string $entity
     * @param  null|EntityMap                $entityMap
     * @throws MappingException
     * @return Mapper
     */
    public static function getMapper($entity, $entityMap = null)
    {
        return static::$instance->mapper($entity, $entityMap);
    }

    /**
     * Create a mapper for a given entity
     *
     * @param  \Analogue\ORM\Mappable|string|array|\Traversable $entity
     * @param  mixed                                            $entityMap
     * @throws MappingException
     * @throws \InvalidArgumentException
     * @return Mapper
     */
    public function mapper($entity, $entityMap = null)
    {
        if ($entity instanceof Wrapper) {
            throw new MappingException('Tried to instantiate mapper on wrapped Entity');
        }

        $entity = $this->resolveEntityClass($entity);

        $entity = $this->getInverseMorphMap($entity);

        // Return existing mapper instance if exists.
        if (array_key_exists($entity, $this->mappers)) {
            return $this->mappers[$entity];
        } else {
            return $this->buildMapper($entity, $entityMap);
        }
    }

    /**
     * This method resolve entity class from mappable instances or iterators
     *
     * @param \Analogue\ORM\Mappable|string|array|\Traversable $entity
     * @return string
     */
    protected function resolveEntityClass($entity)
    {
        switch (true) {
            case Support::isTraversable($entity):
                if (!count($entity)) {
                    throw new \InvalidArgumentException('Length of Entity collection must be greater than 0');
                }

                $firstEntityItem = ($entity instanceof \Iterator || $entity instanceof \IteratorAggregate)
                    ? $entity->current()
                    : current($entity);

                return $this->resolveEntityClass($firstEntityItem);

            case is_object($entity):
                return get_class($entity);

            case !is_string($entity):
                throw new \InvalidArgumentException('Invalid mapper Entity type');
                break;
        }

        return $entity;
    }

    /**
     * @param string $key
     * @return string
     */
    public function getInverseMorphMap($key)
    {
        return array_key_exists($key, $this->morphMap) ? $this->morphMap[$key] : $key;
    }

    /**
     * Build a new Mapper instance for a given Entity
     *
     * @param  string $entity
     * @param         $entityMap
     * @throws MappingException
     * @return Mapper
     */
    protected function buildMapper($entity, $entityMap)
    {
        // If an EntityMap hasn't been manually registered by the user
        // register it at runtime.
        if (!$this->isRegisteredEntity($entity)) {
            $this->register($entity, $entityMap);
        }

        $entityMap = $this->entityClasses[$entity];

        $factory = new MapperFactory($this->drivers, $this->eventDispatcher, $this);

        $mapper = $factory->make($entity, $entityMap);

        $this->mappers[$entity] = $mapper;

        // At this point we can safely call the boot() method on the entityMap as
        // the mapper is now instantiated & registered within the manager.

        $mapper->getEntityMap()->boot();

        return $mapper;
    }

    /**
     * Check if the entity is already registered
     *
     * @param  string|object $entity
     * @return boolean
     */
    public function isRegisteredEntity($entity)
    {
        if (!is_string($entity)) {
            $entity = get_class($entity);
        }

        return array_key_exists($entity, $this->entityClasses);
    }

    /**
     * Register an entity
     *
     * @param  string|\Analogue\ORM\Mappable $entity    entity's class name
     * @param  string|EntityMap              $entityMap map's class name
     * @throws MappingException
     * @return void
     */
    public function register($entity, $entityMap = null)
    {
        // If an object is provider, get the class name from it
        if (!is_string($entity)) {
            $entity = get_class($entity);
        }

        if ($this->isRegisteredEntity($entity)) {
            throw new MappingException("Entity $entity is already registered.");
        }

        if (!class_exists($entity)) {
            throw new MappingException("Class $entity does not exists");
        }

        if (is_null($entityMap)) {
            $entityMap = $this->getEntityMapInstanceFor($entity);
        }

        if (is_string($entityMap)) {
            $entityMap = new $entityMap;
        }

        if (!$entityMap instanceof EntityMap) {
            throw new MappingException(get_class($entityMap) . ' must be an instance of EntityMap.');
        }

        $entityMap->setClass($entity);

        $entityMap->setManager($this);

        $this->entityClasses[$entity] = $entityMap;
    }

    /**
     * Get the entity map instance for a custom entity
     *
     * @param  string $entity
     * @return \Analogue\ORM\Mappable
     */
    protected function getEntityMapInstanceFor($entity)
    {
        if (class_exists($entity . 'Map')) {
            $map = $entity . 'Map';
            $map = new $map;
        } else {
            // Generate an EntityMap object
            $map = $this->getNewEntityMap();
        }

        return $map;
    }

    /**
     * Dynamically create an entity map for a custom entity class
     *
     * @return EntityMap
     */
    protected function getNewEntityMap()
    {
        return new EntityMap;
    }

    /**
     * Return the Singleton instance of the manager
     *
     * @return Manager
     */
    public static function getInstance()
    {
        return static::$instance;
    }

    /**
     * Return the Driver Manager's instance
     *
     * @return \Analogue\ORM\Drivers\Manager
     */
    public function getDriverManager()
    {
        return $this->drivers;
    }

    /**
     * Get the Repository instance for the given Entity
     *
     * @param  \Analogue\ORM\Mappable|string $entity
     * @throws \InvalidArgumentException
     * @throws MappingException
     * @return \Analogue\ORM\Repository
     */
    public function repository($entity)
    {
        if (!is_string($entity)) {
            $entity = get_class($entity);
        }

        // First we check if the repository is not already created.
        if (array_key_exists($entity, $this->repositories)) {
            return $this->repositories[$entity];
        }

        $this->repositories[$entity] = new Repository($this->mapper($entity));

        return $this->repositories[$entity];
    }

    /**
     * Return true is the object is registered as value object
     *
     * @param  mixed $object
     * @return boolean
     */
    public function isValueObject($object)
    {
        if (!is_string($object)) {
            $object = get_class($object);
        }

        return array_key_exists($object, $this->valueClasses);
    }

    /**
     * Get the Value Map for a given Value Object Class
     *
     * @param  string $valueObject
     * @throws MappingException
     * @return \Analogue\ORM\ValueMap
     */
    public function getValueMap($valueObject)
    {
        if (!is_string($valueObject)) {
            $valueObject = get_class($valueObject);
        }

        if (!array_key_exists($valueObject, $this->valueClasses)) {
            $this->registerValueObject($valueObject);
        }
        $valueMap = new $this->valueClasses[$valueObject];

        $valueMap->setClass($valueObject);

        return $valueMap;
    }

    /**
     * Register a Value Object
     *
     * @param  string $valueObject
     * @param  string $valueMap
     * @throws MappingException
     * @return void
     */
    public function registerValueObject($valueObject, $valueMap = null)
    {
        if (!is_string($valueObject)) {
            $valueObject = get_class($valueObject);
        }

        if (is_null($valueMap)) {
            $valueMap = $valueObject . 'Map';
        }

        if (!class_exists($valueMap)) {
            throw new MappingException("$valueMap doesn't exists");
        }

        $this->valueClasses[$valueObject] = $valueMap;
    }

    /**
     * Instantiate a new Value Object instance
     *
     * @param  string $valueObject
     * @return \Analogue\ORM\ValueObject
     */
    public function getValueObjectInstance($valueObject)
    {
        $prototype = unserialize(sprintf('O:%d:"%s":0:{}', strlen($valueObject), $valueObject));

        return $prototype;
    }

    /**
     * Register Analogue Plugin
     *
     * @param  string $plugin class
     * @return void
     */
    public function registerPlugin($plugin)
    {
        $plugin = new $plugin($this);

        $this->events = array_merge($this->events, $plugin->getCustomEvents());

        $plugin->register();
    }

    /**
     * Register event listeners that will be fired regardless the type
     * of the entity.
     *
     * @param  string   $event
     * @param  \Closure $callback
     * @throws \Exception
     * @return void
     */
    public function registerGlobalEvent($event, $callback)
    {
        if (!in_array($event, $this->events)) {
            throw new \Exception("Analogue : Event $event doesn't exist");
        }
        $this->eventDispatcher->listen("analogue.{$event}.*", $callback);
    }

    /**
     * Shortcut to Mapper store
     *
     * @param  mixed $entity
     * @throws MappingException
     * @return mixed
     */
    public function store($entity)
    {
        return $this->mapper($entity)->store($entity);
    }

    /**
     * Shortcut to Mapper delete
     *
     * @param  mixed $entity
     * @throws MappingException
     * @return \Illuminate\Support\Collection|null
     */
    public function delete($entity)
    {
        return $this->mapper($entity)->delete($entity);
    }

    /**
     * Shortcut to Mapper query
     *
     * @param  mixed $entity
     * @throws MappingException
     * @return Query
     */
    public function query($entity)
    {
        return $this->mapper($entity)->query();
    }

    /**
     * Shortcut to Mapper Global Query
     *
     * @param  mixed $entity
     * @throws MappingException
     * @return Query
     */
    public function globalQuery($entity)
    {
        return $this->mapper($entity)->globalQuery();
    }

    /**
     * @param array $morphMap
     * @return $this
     */
    public function morphMap(array $morphMap)
    {
        $this->morphMap = $morphMap;

        return $this;
    }

    /**
     * @param string $class
     * @return mixed
     */
    public function getMorphMap($class)
    {
        $key = array_search($class, $this->morphMap);

        return $key !== false ? $key : $class;
    }
}
