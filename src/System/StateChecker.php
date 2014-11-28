<?php namespace Analogue\ORM\System;

use Analogue\ORM\Entity;
use Analogue\ORM\EntityCollection;

class StateChecker {

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
	 * @param \Analogue\ORM\Entity $entity 
	 * @param \Analogue\ORM\System\Mapper $mapper 
	 */
	public function __construct(Entity $entity, Mapper $mapper)
	{
		$this->entity = $entity;
		$this->mapper = $mapper;
		$this->entityMap = $mapper->getEntityMap();
		$this->keyName = $this->entityMap->getKeyName();
	}	

	/**
	 * Check for entity existence if cache
	 * 
	 * @return boolean
	 */
	public function exists()
	{
		$key = $this->keyName;

		if (is_null($this->entity->$key))
		{
			$this->exists = false;
		}
		else
		{
			$cache = $this->mapper->getEntityCache();
			
			// Check if we have a cache record for the entity
			// which have to be the case.
			if($cache->has($this->entity->$key))
			{
				$this->exists = true;
			}
			else
			{
				// Throw exception (EntityContradiction)
			}
		}

		return $this->exists;
	}

	/**
	 * Chech relations for existence / dirtyness
	 * 
	 * @param  array $relations 
	 * @return 
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

			if($value instanceof Entity)
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

	protected function checkEntityForExistence($entity)
	{
		$checker = $this->newStateChecker($entity);

		return $checker->exists();
	}
	
	
	public function getDirtyAttributes()
	{
		$attributes = $this->entity->getEntityAttributes();

		$id = $attributes[$this->keyName];

		$cachedAttributes = $this->mapper->getEntityCache()->get($id);

		$dirty = [];

		foreach($attributes as $key => $value)
		{
			if ($this->isRelation($key)) continue;

			if ( ! array_key_exists($key, $cachedAttributes))
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
	 * Determine if the new and old values for a given key are numerically equivalent.
	 *
	 * @return bool
	 */
	protected function originalIsNumericallyEquivalent($current, $original)
	{
		return is_numeric($current) && is_numeric($original) && strcmp((string) $current, (string) $original) === 0;
	}


	protected function isRelation($key)
	{
		return in_array($key, $this->entityMap->getRelationships());
	}

	
}