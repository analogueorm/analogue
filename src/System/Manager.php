<?php
namespace Analogue\ORM\System;

use Exception;
use Analogue\ORM\EntityMap;
use Analogue\ORM\Repository;
use Analogue\ORM\System\Mapper;
use Illuminate\Events\Dispatcher;
use Illuminate\Database\DatabaseManager;
use Analogue\ORM\Exceptions\MappingException;
use Analogue\ORM\Plugins\AnaloguePluginInterface;

class Manager {

	/**
	 * Database Manager
	 * 
	 * @var \Illuminate\Database\DatabaseManager
	 */
	protected static $db;

	/**
	 * Key value store of entity classes and corresponding maps.
	 * 
	 * @var array
	 */
	protected static $entityClasses = [];

	/**
	 * Key value store of Value Classes and corresponding maps
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
	 * @var \Illuminate\Events\Dispatcher
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
	 * @param DatabaseManager $db       
	 * @param ProxyCache      $proxyCache 
	 */
	public function __construct(DatabaseManager $db, Dispatcher $event)
	{
		static::$db = $db;

		static::$eventDispatcher = $event;
	}

	/**
	 * Create a mapper for a given entity
	 * 
	 * @param  Entity|String $entity
	 * @return Mapper
	 */
	public static function mapper($entity)
	{
		if(! is_string($entity)) $entity = get_class($entity);

		// Return existing mapper instance if exists.
		if(array_key_exists($entity, static::$mappers))
		{
			return static::$mappers[$entity];
		}

		$entityMap = static::getEntityMapInstanceFor($entity);

		// Check if the entity map is set on a different connection
		// than the default one.
		if ( ($connection = $entityMap->getConnection() ) != null) 
		{
			static::$mappers[$entity] = new Mapper($entityMap, static::$db->connection($connection), static::$eventDispatcher);
		}
		else
		{
			static::$mappers[$entity] = new Mapper($entityMap, static::$db->connection(), static::$eventDispatcher);
		}

		return static::$mappers[$entity];
	}

	/**
	 * Get the Repository instance for the given Entity 
	 * 
	 * @param  Entity|String $entity 
	 * @return Analogue\ORM\Repository
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
	 * Get the entity map instance for a custom entity
	 * 
	 * @param  [type] $entity [description]
	 * @return [type]         [description]
	 */
	protected static function getEntityMapInstanceFor($entity)
	{
		if(! is_string($entity))
		{
			$entity = get_class($entity);
		}

		// If the entity class doesn't exist in the entity array
		// we register it.
		if(! array_key_exists($entity, static::$entityClasses))
		{
			static::register($entity);
		}
		
		$map = static::$entityClasses[$entity];

		if(is_null($map))
		{
			$map = static::createMapForEntity($entity);
		}

		if(is_string($map))
		{
			$map = new $map;
		}
		$map->setClass($entity);
		
		static::$entityClasses[$entity] = $map;

		return $map;

	}	

	/**
	 * Dynamically create an entity map for a custom entity class
	 * 
	 * @param  string $entity 
	 * @return EntityMap         
	 */
	protected static function createMapForEntity($entity)
	{
		$map = new EntityMap;
		
		return $map;
	}

	/**
	 * Register an entity 
	 * 
	 * @param  string|Mappable $entity    entity's class name
	 * @param  string $entityMap map's class name
	 * @return void
	 */
	public static function register($entity, $entityMap = null)
	{
		if(! is_string($entity) ) $entity = get_class($entity);

		if (static::isRegisteredEntity($entity))
		{
			throw new MappingException("Entity $entity is already registered.");
		}

		static::$entityClasses[$entity] = $entityMap;
	}

	/**
	 * Register a Value Object
	 * 
	 * @param  string|ValueObject $valueObject 
	 * @param  string $valueMap    
	 * @return void
	 */
	public static function registerValueObject($valueObject, $valueMap)
	{
		if(! is_string($valueObject) ) $valueObject = get_class($valueObject);

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
	 * [isRegisteredEntity description]
	 * @param  [type]  $entity [description]
	 * @return boolean         [description]
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

}