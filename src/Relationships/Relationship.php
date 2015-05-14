<?php namespace Analogue\ORM\Relationships;

use Closure;
use Carbon\Carbon;
use Analogue\ORM\Mappable;
use Analogue\ORM\System\Query;
use Analogue\ORM\System\Mapper;
use Analogue\ORM\System\Manager;
use Analogue\ORM\EntityCollection;
use Illuminate\Database\Query\Expression;

abstract class Relationship {

	/**
	 * The mapper instance for the related entity
	 * 
	 * @var \Analogue\ORM\System\Mapper
	 */
	protected $relatedMapper;

	/**
	 * The Analogue Query Builder instance.
	 *
	 * @var \Analogue\ORM\Query
	 */
	protected $query;

	/**
	 * The parent entity proxy instance.
	 *
	 * @var object
	 */
	protected $parent;

	/**
	 * The parent entity map
	 * @var \Analogue\ORM\EntityMap
	 */
	protected $parentMap;

	/**
	 * The Parent Mapper instance
	 *
	 * @var \Analogue\ORM\System\Mapper
	 */
	protected $parentMapper;

	/**
	 * The related entity instance.
	 *
	 * @var object
	 */
	protected $related;

	/**
	 * The related entity Map
	 * @var \Analogue\ORM\EntityMap
	 */
	protected $relatedMap;

	/**
	 * Indicate if the parent entity hold the key for the relation.
	 * 
	 * @var boolean
	 */
	protected static $ownForeignKey = false;

	/**
	 * Indicate if the relationships use a pivot table.*
	 * 
	 * @var boolean
	 */
	protected static $hasPivot = false;

	/**
	 * Indicates if the relation is adding constraints.
	 *
	 * @var bool
	 */
	protected static $constraints = true;

	/**
	 * Create a new relation instance.
	 *
	 * @param  \Analogue\ORM\System\Mapper  $mapper
	 * @param  Mappable  $parent
	 * @return void
	 */
	public function __construct(Mapper $mapper, $parent)
	{
		$this->relatedMapper = $mapper;

		$this->query = $mapper->getQuery();

		$this->parent = $parent;

		$this->parentMapper = $mapper->getManager()->getMapper($parent);

		$this->parentMap = $this->parentMapper->getEntityMap();

		$this->related = $this->query->getEntityInstance();

		$this->relatedMap = $mapper->getEntityMap();

		$this->addConstraints();
	}

	/**
	 * 
	 * @param  [type] $related [description]
	 * @return [type]         [description]
	 */
	abstract public function attachTo($related);

	/**
	 * 
	 * @param  [type] $related [description]
	 * @return [type]         [description]
	 */
	abstract public function detachFrom($related);

	/**
	 * Indicate if the parent entity hold the foreign key for relation.
	 * 
	 * @return boolean 
	 */
	public function ownForeignKey()
	{
		return static::$ownForeignKey;
	}

	/**
	 * Indicate if the relationship uses a pivot table
	 * 
	 * @return boolean 
	 */
	public function hasPivot()
	{
		return static::$hasPivot;
	}

	/**
	 * Set the base constraints on the relation query.
	 *
	 * @return void
	 */
	abstract public function addConstraints();

	/**
	 * Set the constraints for an eager load of the relation.
	 *
	 * @param  array  $models
	 * @return void
	 */
	abstract public function addEagerConstraints(array $models);

	/**
	 * Initialize the relation on a set of models.
	 *
	 * @param  array   $models
	 * @param  string  $relation
	 * @return array
	 */
	abstract public function initRelation(array $models, $relation);

	/**
	 * Match the eagerly loaded results to their parents.
	 *
	 * @param  array   $entities
	 * @param  \Analogue\ORM\EntityCollection  $results
	 * @param  string  $relation
	 * @return array
	 */
	abstract public function match(array $entities, EntityCollection $results, $relation);

	/**
	 * Get the results of the relationship.
	 * 
	 * @param string 	$relation 	relation name in parent's entity map
	 * @return mixed
	 */
	abstract public function getResults($relation);

	/**
	 * Get the relationship for eager loading.
	 *
	 * @return \Analogue\ORM\EntityCollection
	 */
	public function getEager()
	{
		return $this->get();
	}

	/**
	 * Add the constraints for a relationship count query.
	 *
	 * @param  \Analogue\ORM\System\Query  $query
	 * @param  \Analogue\ORM\System\Query  $parent
	 * @return \Analogue\ORM\System\Query
	 */
	public function getRelationCountQuery(Query $query, Query $parent)
	{
		$query->select(new Expression('count(*)'));

		$key = $this->wrap($this->getQualifiedParentKeyName());

		return $query->where($this->getHasCompareKey(), '=', new Expression($key));
	}

	/**
	 * Run a callback with constraints disabled on the relation.
	 *
	 * @param  \Closure  $callback
	 * @return mixed
	 */
	public static function noConstraints(Closure $callback)
	{
		static::$constraints = false;

		// When resetting the relation where clause, we want to shift the first element
		// off of the bindings, leaving only the constraints that the developers put
		// as "extra" on the relationships, and not original relation constraints.
		$results = call_user_func($callback);

		static::$constraints = true;

		return $results;
	}

	/**
	 * Get all of the primary keys for an array of entities.
	 *
	 * @param  array   $entities
	 * @param  string  $key
	 * @return array
	 */
	protected function getKeys(array $entities, $key = null)
	{
		if(is_null ($key))
		{
			$key = $this->relatedMap->getKeyName();
		}

		return array_unique(array_values(array_map(function($value) use ($key)
		{
			return $value->getEntityAttribute($key);

		}, $entities)));
	}

	/**
	 * Get the underlying query for the relation.
	 *
	 * @return \Analogue\ORM\System\Query
	 */
	public function getQuery()
	{
		return $this->query;
	}

	/**
	 * Get the base query builder 
	 *
	 * @return \Illuminate\Database\Query\Builder
	 */
	public function getBaseQuery()
	{
		return $this->query->getQuery();
	}

	/**
	 * Get the parent model of the relation.
	 *
	 * @return Mappable
	 */
	public function getParent()
	{
		return $this->parent;
	}

	/**
	 * Get the fully qualified parent key name.
	 *
	 * @return string
	 */
	protected function getQualifiedParentKeyName()
	{
		return $this->parent->getQualifiedKeyName();
	}

	/**
	 * Get the related entity of the relation.
	 *
	 * @return \Analogue\ORM\Entity
	 */
	public function getRelated()
	{
		return $this->related;
	}

	/**
	 * Get the related mapper for the relation
	 * 
	 * @return \Analogue\ORM\System\Mapper
	 */
	public function getRelatedMapper()
	{
		return $this->relatedMapper;
	}


	/**
	 * Get the name of the "created at" column.
	 *
	 * @return string
	 */
	public function createdAt()
	{
		return $this->parentMap->getCreatedAtColumn();
	}

	/**
	 * Get the name of the "updated at" column.
	 *
	 * @return string
	 */
	public function updatedAt()
	{
		return $this->parentMap->getUpdatedAtColumn();
	}

	/**
	 * Get the name of the related model's "updated at" column.
	 *
	 * @return string
	 */
	public function relatedUpdatedAt()
	{
		return $this->related->getUpdatedAtColumn();
	}

	/**
	 * Wrap the given value with the parent query's grammar.
	 *
	 * @param  string  $value
	 * @return string
	 */
	public function wrap($value)
	{
		return $this->parentMapper->getQuery()->getQuery()->getGrammar()->wrap($value);
	}

	/**
	 * Get a fresh timestamp 
	 *
	 * @return \Carbon\Carbon
	 */
	protected function freshTimestamp()
	{
		return new Carbon;
	}

	/**
	 * Cache the link between parent and related
	 * into the mapper's Entity Cache.
	 *  
	 * @param  EntityCollection|Mappable $results 	result of the relation query
	 * @param  string  $relation 					Name of the relation method on the parent entity
	 * 
	 * @return void
	 */
	protected function cacheRelation($results, $relation)
	{
		$cache = $this->parentMapper->getEntityCache();

		$cache->cacheLoadedRelationResult($this->parent, $relation, $results, $this);
	}

	/**
	 * Return Pivot attributes when available on a relationship
	 * 
	 * @return array
	 */
	public function getPivotAttributes()
	{
		return [];
	}	

	/**
	 * Get a combo type.primaryKey
	 * 
	 * @param  Mappable $entity
	 * @return string
	 */
	protected function getEntityHash(Mappable $entity)
	{
		$class = get_class($entity);
		
		$keyName = $this->relatedMapper->getManager()->mapper($class)->getEntityMap()->getKeyName();
		
		$hash = $class.'.'.$entity->getEntityAttribute($keyName);

		return $hash;
	}

	/**
	 * Handle dynamic method calls to the relationship.
	 *
	 * @param  string  $method
	 * @param  array   $parameters
	 * @return mixed
	 */
	public function __call($method, $parameters)
	{
		$result = call_user_func_array(array($this->query, $method), $parameters);

		if ($result === $this->query) return $this;

		return $result;
	}

}
