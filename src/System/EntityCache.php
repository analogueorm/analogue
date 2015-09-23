<?php namespace Analogue\ORM\System;

use Analogue\ORM\Mappable;
use Analogue\ORM\EntityMap;
use Analogue\ORM\EntityCollection;
use Analogue\ORM\Relationships\Relationship;
use Analogue\ORM\System\InternallyMappable;
use Analogue\ORM\System\Wrappers\Factory;

class EntityCache {

	protected $cache = [];

	protected $entityMap;

	protected $factory;

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

		$this->factory = new Factory;
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
	 * @param  mixed $parent   
	 * @param  string   $relation name of the relation
	 * @param  mixed 	$results  results of the relationship's query
	 *
	 * @return void
	 */
	public function cacheLoadedRelationResult($parent, $relation, $results, Relationship $relationship)
	{
		$keyName = $this->entityMap->getKeyName();

		$wrappedParent = $this->factory->make($parent);

		$key = $wrappedParent->getEntityAttribute($keyName);
		
		if ($results instanceof EntityCollection)
		{
			$this->cacheManyRelationResults($key, $relation, $results, $relationship);
		}

		// POPO : Maybe this check isn't needed, or we have to check for stdClass
		// instead
		if ($results instanceof Mappable)
		{
			$this->cacheSingleRelationResult($key, $relation, $results, $relationship);
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

		$wrapper = $this->factory->make($result);

		$hash = $this->getEntityHash($wrapper);

		if(count($pivotColumns) > 0)
		{
			$pivotAttributes = [];
			foreach($pivotColumns as $column)
			{
				$pivotAttributes[$column] = $wrapper->getEntityAttribute('pivot')->getEntityAttribute($column);
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
	 * @param  $entity 
	 * @return string
	 */
	protected function getEntityHash(InternallyMappable $entity)
	{
		$class = get_class($entity->getObject() );

		$mapper = Manager::getMapper($class);

		$keyName = $mapper->getEntityMap()->getKeyName();

		return $class.'.'.$entity->getEntityAttribute($keyName);
	}

	/**
	 * Refresh the cache record for an entity
	 * 
	 * @param  InternallyMappable $entity [description]
	 * @return [type]                     [description]
	 */
	public function refresh(InternallyMappable $entity)
	{
		$class = $entity->getEntityClass();

		$mapper = Manager::getMapper($class);

		$keyName = $mapper->getEntityMap()->getKeyName();

		$this->cache[$entity->getEntityAttribute($keyName)] = $this->cachedArray($entity);
	}

	
	protected function cachedArray(InternallyMappable $entity)
	{
		// Flatten Value Objects as attributes
		$attributes = $this->flattenEmbeddables($entity->getEntityAttributes());

		$cache = [];

		foreach($attributes as $key => $value)
		{
			if ($value instanceof ProxyInterface) continue;

			// POPO : this won't work with Plain Objects
			// we must have the cache be aware of what are the 
			// related class types. 
			// 
			if ($value instanceof Mappable)
			{
				$class = get_class($value);
				
				$mapper = Manager::getMapper($class);
				
				$keyName = $mapper->getEntityMap()->getKeyName();
				
				$wrappedValue = $this->factory->make($value);

				$cache[$key] = new CachedRelationship($class.'.'.$wrappedValue->getEntityAttribute($keyName), $this->getPivotValues($key, $wrappedValue));
				
				continue;
			}

			if ($value instanceof EntityCollection)
			{
				$cache[$key] = [];

				foreach($value as $relatedEntity)
				{
					$wrappedRelated = $this->factory->make($relatedEntity);

					$hash = $this->getEntityHash($wrappedRelated);

					$cache[$key][$hash] = new CachedRelationship($hash, $this->getPivotValues($key, $wrappedRelated));
				}
				
				continue;
			}

			$cache[$key] = $value;
		}
		
		return $cache;
	}

	protected function getPivotValues($relation, InternallyMappable $entity)
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