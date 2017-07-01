<?php

namespace Analogue\ORM\System;

use Analogue\ORM\Drivers\Manager as DriverManager;
use Analogue\ORM\Entity;
use Analogue\ORM\EntityMap;
use Analogue\ORM\Exceptions\EntityMapNotFoundException;
use Analogue\ORM\Exceptions\MappingException;
use Analogue\ORM\Plugins\AnaloguePluginInterface;
use Analogue\ORM\Repository;
use Analogue\ORM\System\Wrappers\Wrapper;
use Analogue\ORM\ValueMap;
use Exception;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Collection;
use Psr\Container\ContainerInterface;

/**
 * This class is the entry point for registering Entities and
 * instansiating Mappers.
 */
class Manager
{
    /**
     * Manager instance.
     *
     * @var Manager
     */
    protected static $instance;

    /**
     * Driver Manager.
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
     * Key value store of ValueObject Classes and corresponding map classes.
     *
     * @var array|ValueMap[]
     */
    protected $valueClasses = [];

    /**
     * Morph map.
     */
    protected $morphMap = [];

    /**
     * Loaded Mappers.
     *
     * @var array
     */
    protected $mappers = [];

    /**
     * Loaded Repositories.
     *
     * @var array
     */
    protected $repositories = [];

    /**
     * Event dispatcher instance.
     *
     * @var \Illuminate\Contracts\Events\Dispatcher
     */
    protected $eventDispatcher;

    /**
     * Container.
     *
     * @var \Psr\Container\ContainerInterface
     */
    protected $container;

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
     * If strictMode is set to true, Manager will throw
     * an exception if no entityMap class are registered
     * for a given entity class.
     *
     * @var bool
     */
    protected $strictMode = true;

    /**
     * We can add namespaces in this array where the manager
     * will look for when auto registering entityMaps.
     *
     * @var array
     */
    protected $customMapNamespaces = [];

    /**
     * @param \Analogue\ORM\Drivers\Manager $driverManager
     * @param Dispatcher                    $event
     */
    public function __construct(DriverManager $driverManager, Dispatcher $event, ContainerInterface $container = null)
    {
        $this->drivers = $driverManager;

        $this->eventDispatcher = $event;

        $this->container = $container;

        static::$instance = $this;
    }

    /**
     * Get container instance.
     *
     * @return ContainerInterface | null
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Create a mapper for a given entity (static alias).
     *
     * @param \Analogue\ORM\Mappable|string $entity
     * @param null|EntityMap                $entityMap
     *
     * @throws MappingException
     * @throws \InvalidArgumentException
     *
     * @return Mapper
     */
    public static function getMapper($entity, $entityMap = null)
    {
        return static::$instance->mapper($entity, $entityMap);
    }

    /**
     * Create a mapper for a given entity.
     *
     * @param \Analogue\ORM\Mappable|string|array|\Traversable $entity
     * @param mixed                                            $entityMap
     *
     * @throws MappingException
     * @throws \InvalidArgumentException
     *
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
     * This method resolve entity class from mappable instances or iterators.
     *
     * @param \Analogue\ORM\Mappable|string|array|\Traversable $entity
     *
     * @throws \InvalidArgumentException
     *
     * @return string
     */
    protected function resolveEntityClass($entity)
    {
        // We first check if the entity is traversable and we'll resolve
        // the entity based on the first item of the object.
        if ($this->isTraversable($entity)) {
            if (!count($entity)) {
                throw new \InvalidArgumentException('Length of Entity collection must be greater than 0');
            }

            $firstEntityItem = ($entity instanceof \Iterator)
                ? $entity->current()
                : current($entity);

            return $this->resolveEntityClass($firstEntityItem);
        }

        if (is_object($entity)) {
            return get_class($entity);
        }

        if (is_string($entity)) {
            return $entity;
        }

        throw new \InvalidArgumentException('Invalid entity type');
    }

    /**
     * @param string $key
     *
     * @return string
     */
    public function getInverseMorphMap($key)
    {
        return array_key_exists($key, $this->morphMap) ? $this->morphMap[$key] : $key;
    }

    /**
     * Build a new Mapper instance for a given Entity.
     *
     * @param string $entity
     * @param        $entityMap
     *
     * @throws MappingException
     *
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
     * Check if the entity is already registered.
     *
     * @param string|Entity $entity
     *
     * @return bool
     */
    public function isRegisteredEntity($entity)
    {
        if (!is_string($entity)) {
            $entity = get_class($entity);
        }

        return array_key_exists($entity, $this->entityClasses);
    }

    /**
     * Return an array containing registered entities & entityMap instances.
     *
     * @return array
     */
    public function getRegisteredEntities()
    {
        return $this->entityClasses;
    }

    /**
     * Check if a value class is already registered.
     *
     * @param string|sdtClass $object
     *
     * @return bool
     */
    public function isRegisteredValueObject($object)
    {
        if (!is_string($object)) {
            $object = get_class($object);
        }

        return array_key_exists($object, $this->valueClasses);
    }

    /**
     * Return true if an object is an array or iterator.
     *
     * @param mixed $argument
     *
     * @return bool
     */
    public function isTraversable($argument)
    {
        return $argument instanceof \Traversable || is_array($argument);
    }

    /**
     * Set strict mode for entityMap instantiation.
     *
     * @param bool $mode
     */
    public function setStrictMode($mode)
    {
        $this->strictMode = $mode;
    }

    /**
     * Register a namespace in where Analogue
     * will scan for EntityMaps & ValueMaps.
     *
     * @param string $namespace
     *
     * @return void
     */
    public function registerMapNamespace($namespace)
    {
        // Add a trailing antislash to namespace if not present
        if (substr('testers', -1) != '\\') {
            $namespace = $namespace.'\\';
        }

        $this->customMapNamespaces[] = $namespace;
    }

    /**
     * Register an entity.
     *
     * @param string|\Analogue\ORM\Mappable $entity    entity's class name
     * @param string|EntityMap              $entityMap map's class name
     *
     * @throws MappingException
     *
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

        if ($entityMap === null) {
            $entityMap = $this->getEntityMapInstanceFor($entity);
        }

        if (is_string($entityMap)) {
            $entityMap = new $entityMap();
        }

        if (!$entityMap instanceof EntityMap) {
            throw new MappingException(get_class($entityMap).' must be an instance of EntityMap.');
        }

        $entityMap->setClass($entity);

        $this->entityClasses[$entity] = $entityMap;
    }

    /**
     * Get the entity map instance for a custom entity.
     *
     * @param string $entity
     *
     * @return \Analogue\ORM\EntityMap
     */
    protected function getEntityMapInstanceFor($entity)
    {
        if (class_exists($entity.'Map')) {
            $map = $entity.'Map';
            $map = new $map();

            return $map;
        }

        if ($map = $this->getMapFromNamespaces($entity)) {
            return $map;
        }

        if ($this->strictMode) {
            throw new EntityMapNotFoundException("No Map registered for $entity");
        }

        $map = $this->getNewEntityMap();

        return $map;
    }

    /**
     * Scan through registered custom namespace
     * for an Entity/ValueMap.
     *
     * @param string $class
     *
     * @return ValueMap|EntityMap|bool
     */
    protected function getMapFromNamespaces($class)
    {
        foreach ($this->customMapNamespaces as $namespace) {
            if ($map = $this->findMapInNamespace($class, $namespace)) {
                return $map;
            }
        }

        return false;
    }

    /**
     * Look in a custom namespace for an Entity/ValueMap.
     *
     * @param string $class
     * @param string $namespace
     *
     * @return ValueMap|EntityMap|bool
     */
    protected function findMapInNamespace($class, $namespace)
    {
        $parts = explode('\\', $class);

        $baseClass = $parts[count($parts) - 1];

        $expectedClass = $namespace.$baseClass.'Map';

        if (class_exists($expectedClass)) {
            return new $expectedClass();
        }

        return false;
    }

    /**
     * Dynamically create an entity map for a custom entity class.
     *
     * @return EntityMap
     */
    protected function getNewEntityMap()
    {
        return new EntityMap();
    }

    /**
     * Return the Singleton instance of the manager.
     *
     * @return Manager
     */
    public static function getInstance()
    {
        return static::$instance;
    }

    /**
     * Return the Driver Manager's instance.
     *
     * @return \Analogue\ORM\Drivers\Manager
     */
    public function getDriverManager()
    {
        return $this->drivers;
    }

    /**
     * Get the Repository instance for the given Entity.
     *
     * @param \Analogue\ORM\Mappable|string $entity
     *
     * @throws \InvalidArgumentException
     * @throws MappingException
     *
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
     * Return true is the object is registered as value object.
     *
     * @param mixed $object
     *
     * @return bool
     */
    public function isValueObject($object)
    {
        if (!is_string($object)) {
            $object = get_class($object);
        }

        return array_key_exists($object, $this->valueClasses);
    }

    /**
     * Get the Value Map for a given Value Object Class.
     *
     * @param string $valueObject
     *
     * @throws MappingException
     *
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

        /** @var ValueMap $valueMap */
        $valueMap = new $this->valueClasses[$valueObject]();

        $valueMap->setClass($valueObject);

        return $valueMap;
    }

    /**
     * Register a Value Object.
     *
     * @param string $valueObject
     * @param string $valueMap
     *
     * @throws MappingException
     *
     * @return void
     */
    public function registerValueObject($valueObject, $valueMap = null)
    {
        if (!is_string($valueObject)) {
            $valueObject = get_class($valueObject);
        }

        if ($valueMap === null) {

            // First, we'll look into registered namespaces for Entity Maps,
            // if any. Then we'll fallback to the same namespace of the object
            if (!$valueMap = $this->getMapFromNamespaces($valueObject)) {
                $valueMap = $valueObject.'Map';
            } else {
                $valueMap = get_class($valueMap);
            }
        }

        if (!class_exists($valueMap)) {
            throw new MappingException("$valueMap doesn't exists");
        }

        $this->valueClasses[$valueObject] = $valueMap;
    }

    /**
     * Instantiate a new Value Object instance.
     *
     * @param string $valueObject
     *
     * @return \Analogue\ORM\ValueObject
     */
    public function getValueObjectInstance($valueObject)
    {
        $prototype = unserialize(sprintf('O:%d:"%s":0:{}', strlen($valueObject), $valueObject));

        return $prototype;
    }

    /**
     * Register Analogue Plugin.
     *
     * @param string $plugin class
     *
     * @return void
     */
    public function registerPlugin($plugin)
    {
        /** @var AnaloguePluginInterface $plugin */
        $plugin = new $plugin($this);

        $this->events = array_merge($this->events, $plugin->getCustomEvents());

        $plugin->register();
    }

    /**
     * Register event listeners that will be fired regardless the type
     * of the entity.
     *
     * @param string   $event
     * @param \Closure $callback
     *
     * @throws \LogicException
     *
     * @return void
     */
    public function registerGlobalEvent($event, $callback)
    {
        if (!array_key_exists($event, $this->events)) {
            throw new \LogicException("Analogue : Event $event doesn't exist");
        }

        $this->eventDispatcher->listen("analogue.{$event}.*", $callback);
        //$this->eventDispatcher->listen($this->events[$event], $callback);
    }

    /**
     * Shortcut to Mapper store.
     *
     * @param mixed $entity
     *
     * @throws MappingException
     * @throws \InvalidArgumentException
     *
     * @return mixed
     */
    public function store($entity)
    {
        return $this->mapper($entity)->store($entity);
    }

    /**
     * Shortcut to Mapper delete.
     *
     * @param mixed $entity
     *
     * @throws MappingException
     * @throws \InvalidArgumentException
     *
     * @return \Illuminate\Support\Collection|null
     */
    public function delete($entity)
    {
        return $this->mapper($entity)->delete($entity);
    }

    /**
     * Shortcut to Mapper query.
     *
     * @param mixed $entity
     *
     * @throws MappingException
     * @throws \InvalidArgumentException
     *
     * @return Query
     */
    public function query($entity)
    {
        return $this->mapper($entity)->query();
    }

    /**
     * Shortcut to Mapper Global Query.
     *
     * @param mixed $entity
     *
     * @throws MappingException
     * @throws \InvalidArgumentException
     *
     * @return Query
     */
    public function globalQuery($entity)
    {
        return $this->mapper($entity)->globalQuery();
    }

    /**
     * @param array $morphMap
     *
     * @return $this
     */
    public function morphMap(array $morphMap)
    {
        $this->morphMap = $morphMap;

        return $this;
    }

    /**
     * @param string $class
     *
     * @return mixed
     */
    public function getMorphMap($class)
    {
        $key = array_search($class, $this->morphMap, false);

        return $key !== false ? $key : $class;
    }
}
