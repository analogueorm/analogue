<?php
namespace Analogue\ORM\System;

use Exception;
use Analogue\ORM\EntityMap;
use Analogue\ORM\Repository;
use Analogue\ORM\System\Mapper;
use Analogue\ORM\Drivers\Manager as DriverManager;
use Analogue\ORM\Exceptions\MappingException;
use Analogue\ORM\Plugins\AnaloguePluginInterface;
use Illuminate\Contracts\Events\Dispatcher;

/**
 * This class keeps track of instanciated mappers, and entity <-> entityMap associations
 */
class Manager {

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
	 * Manager instance
	 * 
	 * @var Manager
	 */
	protected static $instance;

	/**
	 * Available Analogue Events
	 * 
	 * @var array
	 */
	protected $events = ['initializing', 'initialized', 'store', 'stored',
		'creating', 'created', 'updating', 'updated', 'deleting', 'deleted' ];

	/**
	 * @param \Analogue\ORM\Drivers\Manager $driverManager       
	 * @param Dispatcher $event 
	 */
	public function __construct(DriverManager $driverManager, Dispatcher $event)
	{
		$this->drivers = $driverManager;

		$this->eventDispatcher = $event;

		static::$instance = $this;
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
	 * Create a mapper for a given entity
	 * 
	 * @param \Analogue\ORM\Mappable|string $entity
	 * @param mixed $entityMap 
	 * @return Mapper
	 */
	public function mapper($entity, $entityMap = null)
	{
		if(! is_string($entity)) $entity = get_class($entity);

		// Return existing mapper instance if exists.
		if(array_key_exists($entity, $this->mappers))
		{
			return $this->mappers[$entity];
		}

		if(! $this->isRegisteredEntity($entity)) 
		{
			$this->register($entity, $entityMap);
		}

		$entityMap = $this->entityClasses[$entity];

		$factory = new MapperFactory($this->drivers, $this->eventDispatcher, $this);

		$this->mappers[$entity] = $factory->make($entity, $entityMap);

		return $this->mappers[$entity];
	}

	/**
	 * Create a mapper for a given entity (static alias)
	 * 
	 * @param \Analogue\ORM\Mappable|string $entity
	 * @param mixed $entityMap 
	 * @return Mapper
	 */
	public static function getMapper($entity,$entityMap = null)
	{
		return static::$instance->mapper($entity, $entityMap);
	}

	/**
	 * Get the Repository instance for the given Entity 
	 * 
	 * @param  \Analogue\ORM\Mappable|string $entity 
	 * @return \Analogue\ORM\Repository
	 */
	public function repository($entity)
	{
		if(! is_string($entity)) $entity = get_class($entity);

		// First we check if the repository is not already created.
		if(array_key_exists($entity, $this->repositories))
		{
			return $this->repositories[$entity];
		}

		$this->repositories[$entity] = new Repository($this->mapper($entity));
		
		return $this->repositories[$entity];
	}

	/**
	 * Register an entity 
	 * 
	 * @param  string|Mappable $entity    entity's class name
	 * @param  string|EntityMap $entityMap map's class name
	 * @return void
	 */
	public function register($entity, $entityMap = null)
	{
		// If an object is provider, get the class name from it
		if(! is_string($entity) ) $entity = get_class($entity);

		if($this->isRegisteredEntity($entity))
		{
			throw new MappingException("Entity $entity is already registered.");
		}

		if(! class_exists($entity))
		{
			throw new MappingException("Class $entity does not exists");
		}

		if(is_null($entityMap) ) 
		{
			$entityMap = $this->getEntityMapInstanceFor($entity);
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
		
		$this->entityClasses[$entity] = $entityMap;
	}

	/**
     * Get the entity map instance for a custom entity
     * 
     * @param  string   	$entity 
     * @return Mappable
     */
    protected function getEntityMapInstanceFor($entity)
    {
        if (class_exists($entity.'Map'))
        {
            $map = $entity.'Map';
            $map = new $map;
        }
        else 
        {
            // Generate an EntityMap obeject
            $map = $this->getNewEntityMap();
        }
        
        $map->setManager($this);

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
	 * Register a Value Object
	 * 
	 * @param  string|ValueObject $valueObject 
	 * @param  string $valueMap    
	 * @return void
	 */
	public function registerValueObject($valueObject, $valueMap = null)
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

		$this->valueClasses[$valueObject] = $valueMap;
	}

	/**
	 * Get the Value Map for a given Value Object Class
	 * 
	 * @param  string $valueObject 
	 * @return \Analogue\ORM\ValueMap
	 */
	public function getValueMap($valueObject)
	{
		if(! array_key_exists($valueObject, $this->valueClasses))
		{
			$this->registerValueObject($valueObject);
		}
		$valueMap = new $this->valueClasses[$valueObject];

		$valueMap->setClass($valueObject);

		return $valueMap;
	}

	/**
	 * Instanciate a new Value Object instance
	 * 
	 * @param  string $valueObject 
	 * @return ValueObject
	 */
	public function getValueObjectInstance($valueObject)
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
	 * @param  string $plugin class
	 * @return void
	 */
	public function registerPlugin($plugin)
	{
		$plugin = new $plugin($this);

		$this->events = array_merge($this->events, $plugin->getCustomEvents() );

		$plugin->register();
	}

	/**
	 * Check if the entity is already registered
	 * 
	 * @param  string|object  $entity
	 * @return boolean         
	 */
	public function isRegisteredEntity($entity)
	{
		if (! is_string($entity)) $entity = get_class($entity);

		return array_key_exists($entity, $this->entityClasses);
	}

	/**
	 * Register event listeners that will be fired regardless the type
	 * of the entity.
	 * 
	 * @param  string $event  
	 * @param  closure|string $callback 
	 * @return void
	 */
	public function registerGlobalEvent($event, $callback)
	{
		if (! in_array($event, $this->events)) 
		{
			throw new \Exception("Analogue : Event $event doesn't exist");
		}
		$this->eventDispatcher->listen("analogue.{$event}.*", $callback);
	}

	/**
	 * Shortcut to Mapper store
	 * 
	 * @param  mixed $entity
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
	 * @return mixed
	 */
	public function delete($entity)
	{
		return $this->mapper($entity)->delete($entity);
	}

	/**
	 * Shortcut to Mapper query
	 * 
	 * @param  mixed $entity
	 * @return \Analogue\System\Query
	 */
	public function query($entity)
	{
		return $this->mapper($entity)->query();
	}

	/**
	 * Shortcut to Mapper Global Query
	 * 
	 * @param  mixed $entity
	 * @return \Analogue\System\Query
	 */
	public function globalQuery($entity)
	{
		return $this->mapper($entity)->globalQuery();
	}
	
}
