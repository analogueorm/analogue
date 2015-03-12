<?php namespace Analogue\ORM\System;

use Analogue\ORM\Mappable;
use Analogue\ORM\EntityMap;
use Analogue\ORM\EntityCollection;

class EntityCache {

	protected $cache = [];

	protected $entityMap;

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
	 * Cache the results of a relation query 
	 * 
	 * @param  [type] $relation [description]
	 * @param  array  $keys     [description]
	 * @return [type]           [description]
	 */
	public function cacheLoadedRelation($relation, array $keys)
	{
		// The resulting key will be different regarding the relationship
		// 
		// Single : EntityKey -> RelatedKey
		// Many : EntityKey -> (array) RelatedKey
		// MorphSingle : EntityKey -> (RelatedType, RelatedKey)
		// MorphMany : EntityKey -> (array) (RelatedType, RelatedKey)
		// 
		foreach($keys as $key => $value)		
		{
			$cachedEntity = $this->cache[$key];
			if(array_key_exists($relation, $cachedEntity))
			{
				if(is_array($cachedEntity[$relation]) )
				{
					$oldValue = $this->cache[$key][$relation] ;
					$this->cache[$key][$relation] = $value + $oldValue;
				}
				else
				{	
					$this->cache[$key][$relation] = $value;	
				}
			}
			else
			{
				$this->cache[$key][$relation] = $value;
			}
		}
	}

	public function refresh(Mappable $entity)
	{
		$class = get_class($entity);

		$mapper = Manager::mapper($class);

		$keyName = $mapper->getEntityMap()->getKeyName();

		$this->cache[$entity->$keyName] = $this->cachedArray($entity);
	}

	
	protected function cachedArray(Mappable $entity)
	{
		$attributes = $this->flattenEmbeddables($entity->getEntityAttributes());

		$cache = [];

		foreach($attributes as $key => $value)
		{
			if ($value instanceof ProxyInterface) continue;

			if ($value instanceof Mappable)
			{
				$class = get_class($value);
				$mapper = Manager::mapper($class);
				$keyName = $mapper->getEntityMap()->getKeyName();
				$cache[$key] = $class.'.'.$value->$keyName;
				continue;
			}

			if ($value instanceof EntityCollection)
			{
				$cache[$key] = $value->getEntityHashes();
				continue;
			}

			$cache[$key] = $value;
		}
		
		return $cache;
	}

	protected function flattenEmbeddables($attributes)
	{
		$embeddables = $this->entityMap->getEmbeddables();
		
		foreach($embeddables as $localKey => $embed)
		{
			$valueObject = $attributes[$localKey];

			unset($attributes[$localKey]);

			$attributes = array_merge($attributes, $valueObject->toArray());
		}
		
		return $attributes;
	}
}