<?php namespace Analogue\ORM\System;

use Analogue\ORM\Mappable;
use Analogue\ORM\EntityCollection;
use Analogue\ORM\Relationships\Pivot;
use Analogue\ORM\Exceptions\MappingException;

class StateChecker {

	/**
	 * Entity to check
	 * 
	 * @var Mappable
	 */
	protected $entity;

	/**
	 * Entity's mapper instance
	 * 
	 * @var \Analogue\ORM\System\Mapper
	 */
	protected $mapper;

	/**
	 * Entity exist in database
	 * 
	 * @var boolean
	 */
	protected $exists;

	/**
	 * Entity Map
	 * 
	 * @var \Analogue\ORM\EntityMap
	 */
	protected $entityMap;

	/**
	 * Name of key field
	 * 
	 * @var string
	 */
	protected $keyName;
		
	/**
	 * 
	 * @param \Analogue\ORM\Mappable $entity 
	 * @param \Analogue\ORM\System\Mapper $mapper 
	 */
	public function __construct(Mappable $entity, Mapper $mapper)
	{
		$this->entity = $entity;
		$this->mapper = $mapper;
		$this->entityMap = $mapper->getEntityMap();
		$this->keyName = $this->entityMap->getKeyName();
	}	

	/**
	 * Check for entity existence in the EntityCache
	 * 
	 * @return boolean
	 */
	public function exists()
	{
		if (! $this->hasPrimaryKeyDefined() )
		{
			$this->exists = false;
		}
		else
		{
			$cache = $this->mapper->getEntityCache();
			
			$key = $this->entityMap->getKeyName();
			// Check if we have a cache record for the entity
			// which have to be the case.
			if($cache->has($this->entity->getEntityAttribute($key)))
			{
				$this->exists = true;
			}
			else
			{
				$this->exists = false;
			}
		}

		return $this->exists;
	}

	/**
	 * Check if the primary key entity attribute is set
	 * 
	 * @return boolean 
	 */
	protected function hasPrimaryKeyDefined()
	{
		$entityAttributes = $this->entity->getEntityAttributes();

		return array_key_exists($this->keyName, $entityAttributes) ? true : false;
	}

	/**
	 * Chech relations for existence / dirtyness
	 * 
	 * @param  array $relations 
	 * @return array non-existing relationships
	 */
	public function checkExistingRelations($relations)
	{
		$nonExisting = [];
		
		$attributes = $this->entity->getEntityAttributes();

		foreach($relations as $relation)
		{
			$value = $attributes[$relation];

			if(is_null($value)) continue;

			if($value instanceof EntityProxy) continue;

			if($value instanceof CollectionProxy) continue;

			if($value instanceof Mappable)
			{
				if (! $this->checkEntityForExistence($value))
				{
					$nonExisting[] = $value;
				}
			}

			if($value instanceof EntityCollection)
			{
				foreach($value as $entity)
				{
					if (! $this->checkEntityForExistence($value))
					{
						$nonExisting[] = $value;
					}
				}
			}
		}
		return $nonExisting;
	}

	/**
	 * Check if an entity exist in the Entity Cache
	 * 
	 * @param  Mappable $entity 
	 * @return boolean
	 */
	protected function checkEntityForExistence(Mappable $entity)
	{
		$checker = $this->newStateChecker($entity);

		return $checker->exists();
	}
	
	/**
	 * Get the attributes that have been modified since
	 * the entity have been fetched from the database
	 * 
	 * @return array
	 */
	public function getDirtyAttributes()
	{
		$attributes = $this->flattenEmbeddables($this->entity->getEntityAttributes());

		$id = $attributes[$this->keyName];

		$cachedAttributes = $this->mapper->getEntityCache()->get($id);
		
		$dirty = [];

		foreach($attributes as $key => $value)
		{
			if ($this->isRelation($key) || $key == 'pivot') continue;

			if ( ! array_key_exists($key, $cachedAttributes) && ! $value instanceof Pivot)
			{
				$dirty[$key] = $value;
			}
			elseif ($value !== $cachedAttributes[$key] && 
				! $this->originalIsNumericallyEquivalent($value, $cachedAttributes[$key]))
			{
				$dirty[$key] = $value;
			}
		}

		return $dirty;
	}

	/**
	 * Merge any Value Object's attribute with entity's attribute
	 * 
	 * @param  array
	 * @return array
	 */
	protected function flattenEmbeddables(array $attributes)
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

	/**
	 * Determine if the new and old values for a given key are numerically equivalent.
	 *
	 * @return boolean
	 */
	protected function originalIsNumericallyEquivalent($current, $original)
	{
		return is_numeric($current) && is_numeric($original) && strcmp((string) $current, (string) $original) === 0;
	}


	/**
	 * Determine if an entity attribute is a relationship.
	 * 
	 * @param  strin $key 
	 * @return boolean      
	 */
	protected function isRelation($key)
	{
		return in_array($key, $this->entityMap->getRelationships());
	}

	
}