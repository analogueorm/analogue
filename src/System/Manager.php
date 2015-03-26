<?php
namespace Analogue\ORM\System;

use Exception;
use Analogue\ORM\EntityMap;
use Analogue\ORM\Repository;
use Analogue\ORM\System\Mapper;
use Illuminate\Contracts\Events\Dispatcher;
use Analogue\ORM\Drivers\Manager as DriverManager;
use Analogue\ORM\Exceptions\MappingException;
use Analogue\ORM\Plugins\AnaloguePluginInterface;

/**
 * This class keeps track of instanciated mappers, and entity <-> entityMap associations
 */
class Manager {

	/**
	 * Driver Manager
	 * 
	 * @var \Analogue\ORM\Drivers\Manager
	 */
	protected static $drivers;

	/**
	 * Registered entity classes and corresponding map objects.
	 * 
	 * @var array
	 */
	protected static $entityClasses = [];

	/**
	 * Key value store of ValueObject Classes and corresponding map classes
	 * 
	 * @var array
	 */
	protected static $valueClasses = [];

	/**
	 * Loaded Mappers
	 * 
	 * @var array
	 */
	protected static $mappers = [];

	/**
	 * Loaded Repositories
	 *
	 * @var array
	 */
	protected static $repositories = [];

	/**
	 * Event dispatcher instance
	 * 
	 * @var \Illuminate\Contracts\Events\Dispatcher
	 */
	protected static $eventDispatcher;

	/**
	 * Available Analogue Events
	 * 
	 * @var array
	 */
	protected static $events = ['initializing', 'initialized', 'store', 'stored',
		'creating', 'created', 'updating', 'updated', 'deleting', 'deleted' ];

	/**
	 * @param \Analogue\ORM\Drivers\Manager $driverManager       
	 * @param Dispatcher $event 
	 */
	public function __construct(DriverManager $driverManager, Dispatcher $event)
	{
		static::$drivers = $driverManager;

		static::$eventDispatcher = $event;
	}

	/**
	 * Create a mapper for a given entity
	 * 
	 * @param \Analogue\ORM\Mappable|string $entity
	 * @param mixed $entityMap 
	 * @return Mapper
	 */
	public static function mapper($entity, $entityMap = null)
	{
		if(! is_string($entity)) $entity = get_class($entity);

		// Return existing mapper instance if exists.
		if(array_key_exists($entity, static::$mappers))
		{
			return static::$mappers[$entity];
		}

		if(! static::isRegisteredEntity($entity)) 
		{
			static::register($entity, $entityMap);
		}

		$entityMap = static::$entityClasses[$entity];

		$factory = new MapperFactory(static::$drivers, static::$eventDispatcher);

		static::$mappers[$entity] = $factory->make($entity, $entityMap);

		return static::$mappers[$entity];
	}

	/**
	 * Get the Repository instance for the given Entity 
	 * 
	 * @param  \Analogue\ORM\Mappable|string $entity 
	 * @return \Analogue\ORM\Repository
	 */
	public static function repository($entity)
	{
		if(! is_string($entity)) $entity = get_class($entity);

		// First we check if the repository is not already created.
		if(array_key_exists($entity, static::$repositories))
		{
			return static::$repositories[$entity];
		}

		static::$repositories[$entity] = new Repository(static::mapper($entity));
		
		return static::$repositories[$entity];
	}

	/**
	 * Register an entity 
	 * 
	 * @param  string|Mappable $entity    entity's class name
	 * @param  string|EntityMap $entityMap map's class name
	 * @return void
	 */
	public static function register($entity, $entityMap = null)
	{
		// If an object is provider, get the class name from it
		if(! is_string($entity) ) $entity = get_class($entity);

		if (static::isRegisteredEntity($entity))
		{
			throw new MappingException("Entity $entity is already registered.");
		}

		if(is_null($entityMap) ) 
		{
			$entityMap = static::getEntityMapInstanceFor($entity);
		}

		if(is_string($entityMap)) 
		{
			$entityMap = new $entityMap;
		}

		if(! $entityMap instanceof EntityMap)
		{
			throw new MappingException(get_class($entityMap)." must be an instance of EntityMap.");
		}

		$entityMap->setClass($entity);
		
		static::$entityClasses[$entity] = $entityMap;
	}

	/**
     * Get the entity map instance for a custom entity
     * 
     * @param  string   	$entity 
     * @return Mappable
     */
    protected static function getEntityMapInstanceFor($entity)
    {
        if (class_exists($entity.'Map'))
        {
            $map = $entity.'Map';
            $map = new $map;
        }
        else 
        {
            // Generate an EntityMap obeject
            $map = static::getNewEntityMap();
        }
        
        return $map;
    }  

    /**
     * Dynamically create an entity map for a custom entity class
     * 
     * @return EntityMap         
     */
    protected static function getNewEntityMap()
    {
        return new EntityMap;
    }

	/**
	 * Register a Value Object
	 * 
	 * @param  string|ValueObject $valueObject 
	 * @param  string $valueMap    
	 * @return void
	 */
	public static function registerValueObject($valueObject, $valueMap = null)
	{
		if(! is_string($valueObject) ) $valueObject = get_class($valueObject);

		if(is_null($valueMap))
		{
			$valueMap = $valueObject.'Map';
		}

		if(! class_exists($valueMap))
		{
			throw new MappingException("$valueMap doesn't exists");
		}

		static::$valueClasses[$valueObject] = $valueMap;
	}

	/**
	 * Get the Value Map for a given Value Object Class
	 * 
	 * @param  string $valueObject 
	 * @return \Analogue\ORM\ValueMap
	 */
	public static function getValueMap($valueObject)
	{
		if(! array_key_exists($valueObject, static::$valueClasses))
		{
			static::registerValueObject($valueObject);
		}
		$valueMap = new static::$valueClasses[$valueObject];

		$valueMap->setClass($valueObject);

		return $valueMap;
	}

	/**
	 * Instanciate a new Value Object instance
	 * 
	 * @param  string $valueObject 
	 * @return ValueObject
	 */
	public static function getValueObjectInstance($valueObject)
	{
		$prototype = unserialize(sprintf('O:%d:"%s":0:{}',
			strlen($valueObject),
            			$valueObject
         			)
        		);
		return $prototype;
	}

	/**
	 * Register Analogue Plugin
	 * 
	 * @param  AnaloguePluginInterface $plugin 
	 * @return void
	 */
	public static function registerPlugin(AnaloguePluginInterface $plugin)
	{
		$plugin->register();
	}

	/**
	 * Check if the entity is already registered
	 * 
	 * @param  string|object  $entity
	 * @return boolean         
	 */
	public static function isRegisteredEntity($entity)
	{
		if (! is_string($entity)) $entity = get_class($entity);

		return in_array($entity, static::$entityClasses) ? true: false;
	}

	/**
	 * Register event listeners that will be fired regardless the type
	 * of the entity.
	 * 
	 * @param  string $event  
	 * @param  closure|string $callback 
	 * @return void
	 */
	public static function registerGlobalEvent($event, $callback)
	{
		if (! in_array($event, static::$events)) 
		{
			throw new \Exception("Analogue : Event $event doesn't exist");
		}
		static::$eventDispatcher->listen("analogue.{$event}.*", $callback);
	}

	/**
	 * Shortcut to Mapper store
	 * 
	 * @param  mixed $entity
	 * @return mixed
	 */
	public static function store($entity)
	{
		return static::mapper($entity)->store($entity);
	}

	/**
	 * Shortcut to Mapper delete
	 * 
	 * @param  mixed $entity
	 * @return mixed
	 */
	public static function delete($entity)
	{
		return static::mapper($entity)->delete($entity);
	}

	/**
	 * Shortcut to Mapper query
	 * 
	 * @param  mixed $entity
	 * @return \Analogue\System\Query
	 */
	public static function query($entity)
	{
		return static::mapper($entity)->query();
	}

	/**
	 * Shortcut to Mapper Global Query
	 * 
	 * @param  mixed $entity
	 * @return \Analogue\System\Query
	 */
	public static function globalQuery($entity)
	{
		return static::mapper($entity)->globalQuery();
	}
	
}
