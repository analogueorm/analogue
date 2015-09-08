<?php namespace Analogue\ORM\System;

use Analogue\ORM\Mappable;
use Analogue\ORM\EntityMap;
use Analogue\ORM\EntityCollection;
use Analogue\ORM\Relationships\Relationship;

class EntityCache {

	protected $cache = [];

	protected $entityMap;

	/**
	 * Associative array containing list of pivot attributes per relationship
	 * so we don't have to call relationship method on refresh.
	 * 
	 * @var array
	 */
	protected $pivotAttributes = [];

	public function __construct(EntityMap $entityMap)
	{
		$this->entityMap = $entityMap;
	}

	/**
	 * Add an array of key=>attributes representing
	 * the initial state of loaded entities.
	 * 
	 * @param array $entities
	 */
	public function add(array $entities)
	{
		if (count($this->cache) == 0) 
		{
			$this->cache = $entities;
		}
		else
		{
			$this->mergeCacheResults($entities);
		}
	}

	/**
	 * Retrieve initial attributes for a single entity
	 * 
	 * @param  string $id 
	 * @return array
	 */
	public function get($id)
	{
		if($this->has($id))
		{
			return $this->cache[$id];
		}
		else
		{
			return [];
		}
	}

	/**
	 * Check if a record for this id exists.
	 * 
	 * @param  string  $id 
	 * @return boolean     
	 */
	public function has($id)
	{
		return array_key_exists($id, $this->cache);
	}

	/**
	 * Combine new result set with existing attributes in 
	 * cache. 
	 * 
	 * @param  array $entities
	 * @return void
	 */
	protected function mergeCacheResults($entities)
	{
		foreach($entities as $key => $entity)
		{
			if(array_key_exists($key, $this->cache))
			{
				$existingValue = $this->cache[$key];

				$this->cache[$key] = $entity + $existingValue;
			}
			else
			{
				$this->cache[$key] = $entity;
			}
		}
	}

	/**
	 * Cache Relation's query result for an entity
	 * 
	 * @param  Mappable $parent   
	 * @param  string   $relation name of the relation
	 * @param  mixed 	$results  results of the relationship's query
	 *
	 * @return void
	 */
	public function cacheLoadedRelationResult($parent, $relation, $results, Relationship $relationship)
	{
		$keyName = $this->entityMap->getKeyName();

		$key = $parent->getEntityAttribute($keyName);

		if ($results instanceof Mappable)
		{
			$this->cacheSingleRelationResult($key, $relation, $results, $relationship);
		}
		
		if ($results instanceof EntityCollection)
		{
			$this->cacheManyRelationResults($key, $relation, $results, $relationship);
		}
	}

	/**
	 * Create a cachedRelationship instance which will hold related entity's hash and pivot attributes, if any.
	 * 
	 * @param  [type]       $parentKey    [description]
	 * @param  [type]       $relation     [description]
	 * @param  [type]       $result       [description]
	 * @param  Relationship $relationship [description]
	 * @return [type]                     [description]
	 */
	protected function getCachedRelationship($parentKey, $relation, $result, Relationship $relationship)
	{
		$pivotColumns = $relationship->getPivotAttributes();

		if(! array_key_exists($relation, $this->pivotAttributes))
		{
			$this->pivotAttributes[$relation] = $pivotColumns;
		}

		$hash = $this->getEntityHash($result);

		if(count($pivotColumns) > 0)
		{
			$pivotAttributes = [];
			foreach($pivotColumns as $column)
			{
				$pivotAttributes[$column] = $result->getEntityAttribute('pivot')->getEntityAttribute($column);
			}

			$cachedRelationship = new CachedRelationship($hash, $pivotAttributes);
		}
		else
		{
			$cachedRelationship = new CachedRelationship($hash);
		}

		return $cachedRelationship;
	}	

	/**
	 * Cache a many relationship
	 * 
	 * @param  [type]       $parentKey    [description]
	 * @param  [type]       $relation     [description]
	 * @param  [type]       $results      [description]
	 * @param  Relationship $relationship [description]
	 * @return [type]                     [description]
	 */
	protected function cacheManyRelationResults($parentKey, $relation, $results, Relationship $relationship)
	{
		$this->cache[$parentKey][$relation] = [];

		foreach($results as $result)
		{
			$cachedRelationship = $this->getCachedRelationship($parentKey, $relation, $result, $relationship);

			$relatedHash = $cachedRelationship->getHash();

			$this->cache[$parentKey][$relation][$relatedHash] = $cachedRelationship;
		}
	}

	/**
	 * Cache a single relationship 
	 * 
	 * @param  [type]       $parentKey    [description]
	 * @param  [type]       $relation     [description]
	 * @param  [type]       $results      [description]
	 * @param  Relationship $relationship [description]
	 * @return [type]                     [description]
	 */
	protected function cacheSingleRelationResult($parentKey, $relation, $result, Relationship $relationship)
	{
		$this->cache[$parentKey][$relation] = $this->getCachedRelationship($parentKey, $relation, $result, $relationship);
	}

	/**
	 * Get Entity's Hash
	 * 
	 * @param  Mappable $entity 
	 * 
	 * @return string
	 */
	protected function getEntityHash(Mappable $entity)
	{
		$class = get_class($entity);

		$mapper = Manager::getMapper($class);

		$keyName = $mapper->getEntityMap()->getKeyName();

		return $class.'.'.$entity->getEntityAttribute($keyName);
	}


	/**
	 * Cache the results of a relation query (DEPRECATED)
	 * 
	 * @param  [type] $relation [description]
	 * @param  array  $keys     [description]
	 * @return [type]           [description]
	 */
	// public function cacheLoadedRelation($relation, array $keys)
	// {
		
	// 	// The resulting key will be different regarding the relationship
	// 	// 
	// 	// Single : EntityKey -> RelatedKey
	// 	// Many : EntityKey -> (array) RelatedKey
	// 	// MorphSingle : EntityKey -> (RelatedType, RelatedKey)
	// 	// MorphMany : EntityKey -> (array) (RelatedType, RelatedKey)
	// 	// 
	// 	foreach($keys as $key => $value)		
	// 	{
	// 		$cachedEntity = $this->cache[$key];
	// 		if(array_key_exists($relation, $cachedEntity))
	// 		{
	// 			if(is_array($cachedEntity[$relation]) )
	// 			{
	// 				$oldValue = $this->cache[$key][$relation] ;
	// 				$this->cache[$key][$relation] = $value + $oldValue;
	// 			}
	// 			else
	// 			{	
	// 				$this->cache[$key][$relation] = $value;	
	// 			}
	// 		}
	// 		else
	// 		{
	// 			$this->cache[$key][$relation] = $value;
	// 		}
	// 	}
	// }

	public function refresh(Mappable $entity)
	{
		$class = get_class($entity);

		$mapper = Manager::getMapper($class);

		$keyName = $mapper->getEntityMap()->getKeyName();

		$this->cache[$entity->getEntityAttribute($keyName)] = $this->cachedArray($entity);
	}

	
	protected function cachedArray(Mappable $entity)
	{
		// Flatten Value Objects as attributes
		$attributes = $this->flattenEmbeddables($entity->getEntityAttributes());

		$cache = [];

		foreach($attributes as $key => $value)
		{
			if ($value instanceof ProxyInterface) continue;

			if ($value instanceof Mappable)
			{
				$class = get_class($value);
				
				$mapper = Manager::getMapper($class);
				
				$keyName = $mapper->getEntityMap()->getKeyName();
				
				$cache[$key] = new CachedRelationship($class.'.'.$value->$keyName, $this->getPivotValues($key, $value));
				
				continue;
			}

			if ($value instanceof EntityCollection)
			{
				$cache[$key] = [];

				foreach($value as $relatedEntity)
				{
					$hash = $this->getEntityHash($relatedEntity);

					$cache[$key][$hash] = new CachedRelationship($hash, $this->getPivotValues($key, $relatedEntity));
				}
				
				continue;
			}

			$cache[$key] = $value;
		}
		
		return $cache;
	}

	protected function getPivotValues($relation, $entity)
	{
		$values = [];

		$entityAttributes = $entity->getEntityAttributes();

		if(array_key_exists($relation, $this->pivotAttributes))
		{
			foreach($this->pivotAttributes[$relation] as $attribute)
			{
				if(array_key_exists($attribute, $entityAttributes))
				{
					$values[$attribute] = $entity->getEntityAttribute('pivot')->$attribute;
				}
			}
		}

		return $values;
	}

	protected function flattenEmbeddables($attributes)
	{
		$embeddables = $this->entityMap->getEmbeddables();
		
		foreach($embeddables as $localKey => $embed)
		{
			$valueObject = $attributes[$localKey];

			unset($attributes[$localKey]);

			$attributes = array_merge($attributes, $valueObject->getEntityAttributes());
		}
		
		return $attributes;
	}
}