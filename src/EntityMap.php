<?php namespace Analogue\ORM;

use Exception;
use Analogue\ORM\System\Manager;
use Analogue\ORM\System\Mapper;
use Analogue\ORM\Relationships\BelongsTo;
use Analogue\ORM\Relationships\BelongsToMany;
use Analogue\ORM\Relationships\HasMany;
use Analogue\ORM\Relationships\HasManyThrough;
use Analogue\ORM\Relationships\HasOne;
use Analogue\ORM\Relationships\MorphMany;
use Analogue\ORM\Relationships\MorphOne;
use Analogue\ORM\Relationships\MorphTo;
use Analogue\ORM\Relationships\MorphToMany;

/**
 * The Entity Map defines the Mapping behaviour of an Entity,
 * including relationships.
 */
class EntityMap {

	/**
 	 * The mapping driver to use with this entity
	 */
	protected $driver = 'illuminate';

	/**
	 * The Database Connection name for the model.
	 *
	 * @var string
	 */
	protected $connection;

	/**
	 * The table associated with the entity.
	 *
	 * @var string
	 */
	protected $table = null;

	/**
	 * The primary key for the model.
	 *
	 * @var string
	 */
	protected $primaryKey = 'id';

	/**
	 * The Custom Domain Class to use with this mapping
	 * 
	 * @var string
	 */
	protected $class = null;

	/**
	 * Attributes that should be treated as Value Objects
	 * 
	 * @var array
	 */
	protected $embeddables = [];
		
	/**
	 * Determine the relationships method used on the entity.
	 * If not set, mapper will autodetect them
	 * 
	 * @var array
	 */
	private $relationships = null;

	/**
	 * Relationships that should be treated as collection.
	 * 
	 * @var array
	 */
	private $manyRelations = null;

	/**
	 * Relationships that should be treated as single entity.
	 * 
	 * @var array
	 */
	private $singleRelations = null;

	/**
	 * Runtime added relationships
	 * 
	 * @var array
	 */
	protected $dynamicRelationships = [];

	/**
	 * The number of models to return for pagination.
	 *
	 * @var int
	 */
	protected $perPage = 15;

	/**
	 * The relations to eager load on every query.
	 *
	 * @var array
	 */
	protected $with = [];

	/**
	 * The class name to be used in polymorphic relations.
	 *
	 * @var string
	 */
	protected $morphClass;

	/**
	 * Indicates if the entity should be timestamped.
	 *
	 * @var bool
	 */
	public $timestamps = false;

	/**
	 * The name of the "created at" column.
	 *
	 * @var string
	 */
	protected $createdAtColumn = 'created_at';

	/**
	 * The name of the "updated at" column.
	 *
	 * @var string
	 */
	protected $updatedAtColumn = 'updated_at';

	/**
	 * Indicates if the entity uses softdeletes
	 * 
	 * @var boolean
	 */
	public $softDeletes = false;

	/**
	 * The name of the "deleted at" column.
	 * 
	 * @var string
	 */
	protected $deletedAtColumn = 'deleted_at';

	/**
	 * The many to many relationship methods.
	 *
	 * @var array
	 */
	public static $manyMethods = array('belongsToMany', 'morphToMany', 'morphedByMany');

	/**
	 * The date format to use with the current database connection
	 * 
	 * @var string
	 */
	protected $dateFormat;

	/**
	 * The Analogue's manager instance.
	 * 
	 * @var \Analogue\ORM\System\Manager
	 */
	private $manager;

	/**
	 * Set the Manager that will be used for relationship's mapper instantiations.
	 * 
	 * @param Manager $manager 
	 */
	public function setManager(Manager $manager)
	{
		$this->manager = $manager;
	}

	/**
	 * Set the date format to use with the current database connection
	 * 
	 * @param string $format 
	 */
	public function setDateFormat($format)
	{	
		$this->dateFormat = $format;
	}
	
	/**
	 * Get the date format to use with the current database connection
	 *
	 *  @return string
	 */
	public function getDateFormat()
	{
		return $this->dateFormat;
	}

	/**
	 * Set the Driver for this mapping
	 * 
	 * @param string $driver
	 */
	public function setDriver($driver)
	{
		$this->driver = $driver;
	}

	/**
	 * Get the Driver for this mapping.
	 * 
	 * @return string
	 */
	public function getDriver()
	{
		return $this->driver;
	}

	/**
	 * Set the db connection to use on the table
	 * 
	 * @param [type] $connection [description]
	 */
	public function setConnection($connection)
	{
		$this->connection = $connection;
	}

	/**
	 * Get the Database connection the Entity is stored on.
	 * 
	 * @return string
	 */
	public function getConnection()
	{
		return $this->connection;
	}

	/**
	 * Get the table associated with the entity.
	 *
	 * @return string
	 */
	public function getTable()
	{
		if (! is_null($this->table)) return $this->table;
		
		return str_replace('\\', '', snake_case(str_plural(class_basename($this->getClass() ))));
	}

	/**
	 * Set the database table name
	 * 
	 * @param  string $table 
	 */
	public function setTable($table)
	{
		$this->table = $table;
	}

	/**
	 * Get the custom entity class
	 * 
	 * @return string namespaced class name
	 */
	public function getClass()
	{
		return isset($this->class) ? $this->class : null;
	}

	/**
	 * Set the custom entity class
	 * 
	 * @param  string namespaced class name
	 */
	public function setClass($class)
	{	
		// Throw exception if class not exists
		
		$this->class = $class;
	}

	/**
	 * Get the embedded Value Objects
	 * 
	 * @return array
	 */
	public function getEmbeddables()
	{
		return $this->embeddables;
	}

	/**
	 * Set the embedded Value Objects
	 * 
	 * @param array $embeddables 
	 */
	public function setEmbeddables(array $embeddables)
	{
		$this->embeddables = $embeddables;
	}

	/**
	 * Get the relationships to map on a custom domain
	 * class.
	 * 
	 * @return array
	 */
	public function getRelationships()
	{
		return $this->relationships;
	}

	/**
	 * Set the relationships method that are used on 
	 * the entity map
	 * 
	 * @return void
	 */
	public function setRelationships($relationships)
	{
		$this->relationships = $relationships;
	}

	/**
	 * Set Relationships of the Entity type
	 * 
	 * @return array
	 */
	public function setSingleRelationships(array $singleRelations)
	{
		$this->singleRelations = $singleRelations;
	}

	/**
	 * Relationships of the Entity type
	 * 
	 * @return array
	 */
	public function getSingleRelationships()
	{
		return $this->singleRelations;
	}

	/**
	 * Set Relationships of type Collection
	 * 
	 * @param array $manyRelations 
	 */
	public function setManyRelationships(array $manyRelations)
	{
		$this->manyRelations = $manyRelations;
	}

	/**
	 * Relationships of type Collection
	 * 
	 * @return array
	 */
	public function getManyRelationships()
	{
		return $this->manyRelations;
	}

	/**
	 * Are the relations method been parsed?
	 * 
	 * @return boolean
	 */
	public function relationsParsed()
	{
		return ! is_null($this->singleRelations);
	}

	/**
	 * Add a Relationship method at runtime.
	 * 
	 * @param string  $name         Relation name
	 * @param Closure $relationship 
	 *
	 * @return void
	 */
	public function addRelationshipMethod($name, Closure $relationship )
	{
		$this->dynamicRelationships[$name] = $relationship;
	}

	/**
	 * Get the dynamic relationship method names.
	 *
	 * @return array
	 */
	public function getDynamicRelationships()
	{
		return array_keys($this->dynamicRelationships);
	}

	/**
	 * Get the relationships that have to be eager loaded
	 * on each request.
	 * 
	 * @return array
	 */
	public function getEagerloadedRelationships()
	{
		return $this->with;
	}

	/**
	 * Get the primary key for the entity.
	 *
	 * @return string
	 */
	public function getKeyName()
	{
		return $this->primaryKey;
	}
	
	/**
	 * Set the primary key for the entity.
	 *
	 * @return void
	 */
	public function setKeyName($key)
	{
		$this->primaryKey = $key;
	}
	
	/**
	 * Get the table qualified key name.
	 *
	 * @return string
	 */
	public function getQualifiedKeyName()
	{
		return $this->getTable().'.'.$this->getKeyName();
	}

	/**
	 * Get the number of models to return per page.
	 *
	 * @return int
	 */
	public function getPerPage()
	{
		return $this->perPage;
	}

	/**
	 * Set the number of models to return per page.
	 *
	 * @param  int   $perPage
	 * @return void
	 */
	public function setPerPage($perPage)
	{
		$this->perPage = $perPage;
	}

	/**
	 * Determine if the entity uses get.
	 *
	 * @return bool
	 */
	public function usesTimestamps()
	{
		return $this->timestamps;
	}

	/**
	 * Determine if the entity uses soft deletes
	 *
	 * @return bool
	 */
	public function usesSoftDeletes()
	{
		return $this->softDeletes;
	}

	/**
	 * Get the 'created_at' column name
	 * 
	 * @return string
	 */
	public function getCreatedAtColumn()
	{
		return $this->createdAtColumn;
	}

	/**
	 * Get the 'updated_at' column name
	 * 
	 * @return string
	 */
	public function getUpdatedAtColumn()
	{
		return $this->updatedAtColumn;
	}

	/**
	 * Get the deleted_at column
	 * 
	 * @return string
	 */
	public function getQualifiedDeletedAtColumn()
	{
		return $this->deletedAtColumn;
	}

	/**
	 * Get the default foreign key name for the model.
	 *
	 * @return string
	 */
	public function getForeignKey()
	{
		return snake_case(class_basename($this->getClass() )).'_id';
	}

	/**
	 * Define a one-to-one relationship.
	 *
	 * @param  $entity
	 * @param  string  $related entity class
	 * @param  string  $foreignKey
	 * @param  string  $localKey
	 * @return \Analogue\ORM\Relationships\HasOne
	 */
	public function hasOne($entity, $relatedClass, $foreignKey = null, $localKey = null)
	{
		$foreignKey = $foreignKey ?: $this->getForeignKey();

		$relatedMapper = $this->manager->mapper($relatedClass);

		$relatedMap = $relatedMapper->getEntityMap();

		$localKey = $localKey ?: $this->getKeyName();

		return new HasOne($relatedMapper, $entity, $relatedMap->getTable().'.'.$foreignKey, $localKey);
	}

	/**
	 * Define a polymorphic one-to-one relationship.
	 *
	 * @param  string  $related
	 * @param  string  $name
	 * @param  string  $type
	 * @param  string  $id
	 * @param  string  $localKey
	 * @return \Analogue\ORM\Relationships\MorphOne
	 */
	public function morphOne($entity, $related, $name, $type = null, $id = null, $localKey = null)
	{
		list($type, $id) = $this->getMorphs($name, $type, $id);

		$localKey = $localKey ?: $this->getKeyName();

		$relatedMapper = $this->manager->mapper($related);

		$table = $relatedMapper->getEntityMap()->getTable();
		
		return new MorphOne($relatedMapper, $entity, $table.'.'.$type, $table.'.'.$id, $localKey);
	}

	/**
	 * Define an inverse one-to-one or many relationship.
	 *
	 * @param  string  $related
	 * @param  string  $foreignKey
	 * @param  string  $otherKey
	 * @param  string  $relation
	 * @return \Analogue\ORM\Relationships\BelongsTo
	 */
	public function belongsTo($entity, $related, $foreignKey = null, $otherKey = null, $relation = null)
	{
		// If no relation name was given, we will use this debug backtrace to extract
		// the calling method's name and use that as the relationship name as most
		// of the time this will be what we desire to use for the relationships.
		if (is_null($relation))
		{
			list(, $caller) = debug_backtrace(false);

			$relation = $caller['function'];
		}

		// If no foreign key was supplied, we can use a backtrace to guess the proper
		// foreign key name by using the name of the relationship function, which
		// when combined with an "_id" should conventionally match the columns.
		if (is_null($foreignKey))
		{
			$foreignKey = snake_case($relation).'_id';
		}

		$relatedMapper = $this->manager->mapper($related);

		$otherKey = $otherKey ?: $relatedMapper->getEntityMap()->getKeyName();

		return new BelongsTo($relatedMapper, $entity, $foreignKey, $otherKey, $relation);
	}

	/**
	 * Define a polymorphic, inverse one-to-one or many relationship.
	 *
	 * @param  string  $name
	 * @param  string  $type
	 * @param  string  $id
	 * @return \Analogue\ORM\Relationships\MorphTo
	 */
	public function morphTo($entity, $name = null, $type = null, $id = null)
	{
		// If no name is provided, we will use the backtrace to get the function name
		// since that is most likely the name of the polymorphic interface. We can
		// use that to get both the class and foreign key that will be utilized.
		if (is_null($name))
		{
			list(, $caller) = debug_backtrace(false);

			$name = snake_case($caller['function']);
		}

		list($type, $id) = $this->getMorphs($name, $type, $id);
		
		$mapper = $this->manager->mapper(get_class($entity));

		// If the type value is null it is probably safe to assume we're eager loading
		// the relationship. When that is the case we will pass in a dummy query as
		// there are multiple types in the morph and we can't use single queries.
		if (is_null($class = $entity->getEntityAttribute($type)))
		{
			return new MorphTo(
				$mapper, $entity, $id, null, $type, $name
			);
		}

		// If we are not eager loading the relationship we will essentially treat this
		// as a belongs-to style relationship since morph-to extends that class and
		// we will pass in the appropriate values so that it behaves as expected.
		else
		{
			$relatedMapper = $this->manager->mapper($class);

			$foreignKey = $relatedMapper->getEntityMap()->getKeyName();

			return new MorphTo(
				$mapper, $entity, $id, $foreignKey, $type, $name
			);
		}
	}

	/**
	 * Define a one-to-many relationship.
	 *
	 * @param  string  $related
	 * @param  string  $foreignKey
	 * @param  string  $localKey
	 * @return \Analogue\ORM\Relationships\HasMany
	 */
	public function hasMany($entity, $related, $foreignKey = null, $localKey = null)
	{
		$foreignKey = $foreignKey ?: $this->getForeignKey();

		$relatedMapper = $this->manager->mapper($related);

		$table = $relatedMapper->getEntityMap()->getTable().'.'.$foreignKey;

		$localKey = $localKey ?: $this->getKeyName();

		return new HasMany($relatedMapper, $entity, $table , $localKey);
	}

	/**
	 * Define a has-many-through relationship.
	 *
	 * @param  string  $related
	 * @param  string  $through
	 * @param  string|null  $firstKey
	 * @param  string|null  $secondKey
	 * @return \Analogue\ORM\Relationships\HasManyThrough
	 */
	public function hasManyThrough($entity, $related, $through, $firstKey = null, $secondKey = null)
	{
		$relatedMapper = $this->manager->mapper($related);

		$throughMapper = $this->manager->mapper($through);


		$firstKey = $firstKey ?: $this->getForeignKey();

		$throughMap = $throughMapper->getEntityMap();

		$secondKey = $secondKey ?: $throughMap->getForeignKey();

		return new HasManyThrough($relatedMapper, $entity, $throughMap, $firstKey, $secondKey);
	}

	/**
	 * Define a polymorphic one-to-many relationship.
	 *
	 * @param  string  $related
	 * @param  string  $name
	 * @param  string  $type
	 * @param  string  $id
	 * @param  string  $localKey
	 * @return \Analogue\ORM\Relationships\MorphMany
	 */
	public function morphMany($entity, $related, $name, $type = null, $id = null, $localKey = null)
	{
		// Here we will gather up the morph type and ID for the relationship so that we
		// can properly query the intermediate table of a relation. Finally, we will
		// get the table and create the relationship instances for the developers.
		list($type, $id) = $this->getMorphs($name, $type, $id);

		$relatedMapper = $this->manager->mapper($related);

		$table = $relatedMapper->getEntityMap()->getTable();

		$localKey = $localKey ?: $this->getKeyName();
		
		return new MorphMany($relatedMapper, $entity, $table.'.'.$type, $table.'.'.$id, $localKey);
	}

	/**
	 * Define a many-to-many relationship.
	 *
	 * @param  string  $related
	 * @param  string  $table
	 * @param  string  $foreignKey
	 * @param  string  $otherKey
	 * @param  string  $relation
	 * @return \Analogue\ORM\Relationships\BelongsToMany
	 */
	public function belongsToMany($entity, $related, $table = null, $foreignKey = null, $otherKey = null, $relation = null)
	{
		// If no relationship name was passed, we will pull backtraces to get the
		// name of the calling function. We will use that function name as the
		// title of this relation since that is a great convention to apply.
		if (is_null($relation))
		{
			$relation = $this->getBelongsToManyCaller();
		}

		// First, we'll need to determine the foreign key and "other key" for the
		// relationship. Once we have determined the keys we'll make the query
		// instances as well as the relationship instances we need for this.
		$foreignKey = $foreignKey ?: $this->getForeignKey();

		$relatedMapper = $this->manager->mapper($related);

		$relatedMap = $relatedMapper->getEntityMap();

		$otherKey = $otherKey ?: $relatedMap->getForeignKey();

		// If no table name was provided, we can guess it by concatenating the two
		// models using underscores in alphabetical order. The two model names
		// are transformed to snake case from their default CamelCase also.
		if (is_null($table))
		{
			$table = $this->joiningTable($relatedMap);
		}

		return new BelongsToMany($relatedMapper, $entity, $table, $foreignKey, $otherKey, $relation);
	}

	/**
	 * Define a polymorphic many-to-many relationship.
	 *
	 * @param  string  $related
	 * @param  string  $name
	 * @param  string  $table
	 * @param  string  $foreignKey
	 * @param  string  $otherKey
	 * @param  bool    $inverse
	 * @return \Analogue\ORM\Relationships\MorphToMany
	 */
	public function morphToMany($entity, $related, $name, $table = null, $foreignKey = null, $otherKey = null, $inverse = false)
	{
		$caller = $this->getBelongsToManyCaller();

		// First, we will need to determine the foreign key and "other key" for the
		// relationship. Once we have determined the keys we will make the query
		// instances, as well as the relationship instances we need for these.
		$foreignKey = $foreignKey ?: $name.'_id';

		$relatedMapper = $this->manager->mapper($related);

		$otherKey = $otherKey ?: $relatedMapper->getEntityMap()->getForeignKey();

		$table = $table ?: str_plural($name);

		return new MorphToMany(
			$relatedMapper, $entity, $name, $table, $foreignKey,
			$otherKey, $caller, $inverse
		);
	}

	/**
	 * Define a polymorphic, inverse many-to-many relationship.
	 *
	 * @param  string  $related
	 * @param  string  $name
	 * @param  string  $table
	 * @param  string  $foreignKey
	 * @param  string  $otherKey
	 * @return \Analogue\ORM\Relationships\MorphToMany
	 */
	public function morphedByMany($entity, $related, $name, $table = null, $foreignKey = null, $otherKey = null)
	{
		$foreignKey = $foreignKey ?: $this->getForeignKey();

		// For the inverse of the polymorphic many-to-many relations, we will change
		// the way we determine the foreign and other keys, as it is the opposite
		// of the morph-to-many method since we're figuring out these inverses.
		$otherKey = $otherKey ?: $name.'_id';

		return $this->morphToMany($entity, $related, $name, $table, $foreignKey, $otherKey, true);
	}

	/**
	 * Get the relationship name of the belongs to many.
	 *
	 * @return  string
	 */
	protected function getBelongsToManyCaller()
	{
		$self = __FUNCTION__;

		$caller = array_first(debug_backtrace(false), function($key, $trace) use ($self)
		{
			$caller = $trace['function'];

			return ( ! in_array($caller, EntityMap::$manyMethods) && $caller != $self);
		});

		return ! is_null($caller) ? $caller['function'] : null;
	}

	/**
	 * Get the joining table name for a many-to-many relation.
	 *
	 * @param  string  $related
	 * @return string
	 */
	public function joiningTable($relatedMap)
	{
		// The joining table name, by convention, is simply the snake cased models
		// sorted alphabetically and concatenated with an underscore, so we can
		// just sort the models and join them together to get the table name.
		$base = $this->getTable();

		$related = $relatedMap->getTable();

		$tables = array($related, $base);

		// Now that we have the model names in an array we can just sort them and
		// use the implode function to join them together with an underscores,
		// which is typically used by convention within the database system.
		sort($tables);

		return strtolower(implode('_', $tables));
	}

	/**
	 * Get the polymorphic relationship columns.
	 *
	 * @param  string  $name
	 * @param  string  $type
	 * @param  string  $id
	 * @return array
	 */
	protected function getMorphs($name, $type, $id)
	{
		$type = $type ?: $name.'_type';

		$id = $id ?: $name.'_id';

		return array($type, $id);
	}

	/**
	 * Get the class name for polymorphic relations.
	 *
	 * @return string
	 */
	public function getMorphClass()
	{
		return $this->morphClass ?: get_class($this);
	}
	
	/**
	 * Create a new Entity Collection instance.
	 *
	 * @param  array  $entities
	 * @return \Analogue\ORM\EntityCollection
	 */
	public function newCollection(array $entities = array())
	{
		return new EntityCollection($entities, $this);
	}

	/**
	 * Override this method for custom entity instantiation
	 * 
	 * @return mixed
	 */
	public function activator()
	{
		return null;
	}

	/**
	 * Call dynamic relationship, if it exists
	 * 
	 * @param  string  $method
	 * @param  array   $parameters
	 * @return mixed
	 */
	public function __call($method, $parameters)
	{
		if(! array_key_exists($method, $this->dynamicRelationships))
		{
			throw new Exception(get_class($this)." has no method $method");
		}

		// Add $this to parameters so the closure can call relationship method on the map.
		$parameters[] = $this;

		return  call_user_func_array(array($this->dynamicRelationships[$method], $parameters));
	}
}
