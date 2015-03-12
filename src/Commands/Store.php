<?php namespace Analogue\ORM\Commands;

use Analogue\ORM\Mappable;
use Analogue\ORM\System\Manager;
use Analogue\ORM\EntityCollection;
use Analogue\ORM\System\EntityProxy;
use Analogue\ORM\System\StateChecker;
use Analogue\ORM\System\ProxyInterface;
use Analogue\ORM\System\MapInitializer;
use Analogue\ORM\System\CollectionProxy;

/**
 * Persist entities & relationships to the
 * database.
 */
class Store extends Command 
{
	/**
	 * List of relationships which foreign key
	 * is part of the entity's attributes
	 * 
	 * @var array
	 */
	protected $masterRelations = [];

	/**
	 * List of relationships owned by current entity
	 * 
	 * @var array
	 */
	protected $slaveRelations = [];	

	/**
	 * Related entities with modified attributes
	 * 
	 * @var array
	 */
	protected $dirtyRelations = [];

	/**
	 * List of relationships using pivot tables
	 * 
	 * @var array
	 */
	protected $relationsWithPivot = [];

	/**
	 * List of Many-to-many relationships that already
	 * exist in the database
	 * 
	 * @var array
	 */
	protected $existingPivotEntities = [];

	/**
	 * List of Many-to-many relationships that don't
	 * exist in the database
	 * 
	 * @var array
	 */
	protected $newPivotEntities = [];

	/**
	 * Persist the entity in the database
	 * 
	 * @return void
	 */
	public function execute()
	{
		$entity = $this->entity;
		$mapper = $this->mapper;

		if ($mapper->fireEvent('storing', $entity) === false)
		{
			return false;
		}

		$this->preStoreProcess();

		if (! $this->entityState->exists() ) 
		{
			if ($mapper->fireEvent('creating', $entity) === false)
			{
				return false;
			}
			
			$this->insert();

			$mapper->fireEvent('created', $entity, false);
		}
		else
		{
			if ($mapper->fireEvent('updating', $entity) === false)
			{
				return false;
			}
			$this->update();

			$mapper->fireEvent('updated', $entity, false);
		}

		$this->postStoreProcess();

		$mapper->fireEvent('stored', $entity, false);

		return $entity;
	}

	/**
	 * Run all operations that have to occur before actually
	 * storing the entity
	 * 
	 * @return void
	 */
	protected function preStoreProcess()
	{
		// First we parse all the relationship of the entity to split
		// them into different categories 
		$this->parseRelations();
		
		// Then if needed, we create any relation that the 
		// entity belongs to.
		if(count($this->masterRelations) > 0)
		{
			$this->createRelatedIfNotExists($this->masterRelations);
			
			// Then, we set the master relations foreign keys
			// on the entity.
			$this->attachRelations($this->masterRelations);
		}
		
		// As storing the entity will reset the original relationships,
		// we must parse for removed relations before storing it.
		$this->detachMissingRelations();
	}

	/**
	 * Parse loaded relations and order them relative to their
	 * ownership direction toward the current entity.
	 * 
	 * @return void
	 */
	protected function parseRelations()
	{
		$entity = $this->entity;

		$entityMap = $this->entityMap;

		$relations = $entityMap->getRelationships();

		foreach($relations as $relation)
		{
			$currentRelation = $entityMap->$relation($entity);
			
			if($currentRelation->ownForeignKey())
			{
				$this->masterRelations[] = $relation;
			}
			else
			{
				if ($currentRelation->hasPivot() )
				{
					$this->relationsWithPivot[] = $relation;
				}
				else 
				{
					$this->slaveRelations[] = $relation;
				}
			}
		}
	}

	/**
	 * Create related Entities if they don't exist
	 * in the database
	 * 
	 * @param  string $relations 
	 * @return void            
	 */
	protected function createRelatedIfNotExists($relations)
	{
		$attributes = $this->getAttributes();

		foreach($relations as $relation)
		{
			if (! array_key_exists($relation, $attributes)) continue;

			$value = $attributes[$relation];

			if(is_null($value)) continue;

			if($value instanceof EntityProxy) continue;

			if($value instanceof CollectionProxy) continue;

			if($value instanceof Mappable)
			{
				$this->createEntityIfNotExists($value);
			}
			if($value instanceof EntityCollection)
			{
				foreach ($value as $entity)
				{
					$this->createEntityIfNotExists($entity);
				}
			}
		}
	}

	/**
	 * Run a store command on an entity which doesn't exist.
	 * 
	 * @param  mixed $entity 
	 * @return void        
	 */
	protected function createEntityIfNotExists($entity)
	{
		$mapper = Manager::mapper($entity);

		$checker = new StateChecker($entity, $mapper);

		if(! $checker->exists())
		{
			$store = new Store($entity, $mapper, $this->query->newQuery());
			$store->execute();
		}
	}

	/**
	 * Detach any relation that have been removed
	 * from the entity.
	 * 
	 * @return void
	 */
	protected function detachMissingRelations()
	{
		$attributes = $this->getAttributes();

		$cachedAttributes = $this->getCachedAttributes();

		$relationships = $this->entityMap->getRelationships();

		foreach ($relationships as $relation)
		{
			if(! array_key_exists($relation, $cachedAttributes)) continue;

			$value = $attributes[$relation];
			$cachedValue = $cachedAttributes[$relation];

			if(is_string($cachedValue))
			{
				if (is_null($value))
				{
					$this->entityMap->$relation($this->entity)->detachFrom($cachedValue);

					continue;
				}
				if ($value instanceof Mappable)
				{
					$hash = $this->getEntityHash($value);

					if($hash !== $cachedValue)
					{
						$this->entityMap->$relation($this->entity)->detachFrom($cachedValue);
					}
					continue;
				}
				// Throw MappingException
			}

			if(is_array($cachedValue))
			{
				if (is_null($value))
				{
					$this->entityMap->$relation($this->entity)->detachMany($cachedValue);
					
					continue;
				}
				if ($value instanceof EntityCollection)
				{
					$hashes = $value->getEntityHashes();

					$missing = array_diff($cachedValue, $hashes);

					$this->entityMap->$relation($this->entity)->detachMany($missing);
				}

				// Throw MappingException
			}
		}
	}

	protected function getEntityHash(Mappable $entity)
	{
		$mapper = Manager::mapper($entity);

		$key = $mapper->getEntityMap()->getKeyName();

		return get_class($entity).'.'.$entity->$key;
	}

	/**
	 * Return the entity's attributes
	 * 
	 * @return array
	 */
	protected function getAttributes()
	{
		return $this->flattenEmbeddables($this->entity->getEntityAttributes());
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

	/**
	 * Return the attribute array in the cache
	 * 
	 * @return array
	 */
	protected function getCachedAttributes()
	{
		$id = $this->getId();

		if(is_null($id))
		{
			return [];
		}

		return $this->mapper->getEntityCache()->get($id);
	}

	protected function getId()
	{
		$keyName = $this->entityMap->getKeyName();

		return $this->entity->getEntityAttribute($keyName);
	}

	protected function postStoreProcess()
	{
		// Set the foreign keys on related owned entities.
		$this->attachRelations($this->slaveRelations);

		// Once the keys are set, we can store the related 
		// entities in the databse.
		$this->createRelatedIfNotExists($this->slaveRelations);
		$this->createRelatedIfNotExists($this->relationsWithPivot);
		
		// Update any pivot tables that has been modified.
		$this->updatePivotRelations($this->relationsWithPivot);

		// Update any dirty relationship
		$this->updateDirtyRelated();

		if(count($this->entityMap->getRelationships()) >0)
		{
			$this->setProxies($this->entity);
		}

		// Update Entity Cache
		$this->mapper->getEntityCache()->refresh($this->entity);
	}
	
	/**
	 * Update foreign keys on related entities
	 * 
	 * @param  array  $relations 
	 * @return void
	 */
	protected function attachRelations(array $relations)
	{
		$attributes = $this->getAttributes();

		foreach($relations as $relation)
		{
			if (! array_key_exists($relation, $attributes)) continue;

			$related = $attributes[$relation];
		
			if ($related instanceof ProxyInterface) continue;

			if(! is_null($related) )
			{
				$this->entityMap->$relation($this->entity)->attachTo($related);
			}
		}
	}

	/**
	 * Parse Many-to-many relationships and create pivot records
	 * if needed
	 * 
	 * @param  array  $relations 
	 * @return void
	 */
	protected function updatePivotRelations(array $relations)
	{
		$attributes = $this->getAttributes();
		$cachedAttributes = $this->getCachedAttributes();

		foreach($relations as $relation)
		{
			if (! array_key_exists($relation, $attributes)) continue;

			if (is_null($attributes[$relation])) continue;

			if ($attributes[$relation] instanceof ProxyInterface) continue;

			// We need to parse the related entities and compare
			// them to the key array we have in cache,which will
			// determine if we need to create a new pivot record
			$value = $attributes[$relation];

			$hashes = $value->getEntityHashes();
			
			if (array_key_exists($relation, $cachedAttributes))
			{
				// Compare the two
				$new = array_diff($hashes, $cachedAttributes[$relation]);
				$existing = array_intersect($hashes, $cachedAttributes[$relation]); 
			}
			else
			{
				$existing = [];
				$new = $hashes;
			}

			// Note : this is were the partial update when using collection 
			// proxy will be implemented
			if(count($new) > 0)
			{
				$pivots = $value->getSubsetByHashes($new);

				$this->entityMap->$relation($this->entity)->createPivots($pivots);
			}

			if(count($existing) > 0)
			{
				$pivots = $value->getSubsetByHashes($existing);

				foreach($pivots as $pivot)
				{
					// We need to store pivot data in the parent cache
					// not in the related entity cache as it's the
					// case now.
					//$this->updatePivotIfDirty($pivot, $relation);
				}
			}

		}

	}

	/**
	 * Update a pivot table if its attributes have changed.
	 * 
	 * @param  object $entity   
	 * @param  string $relation 
	 * @return void
	 */
	protected function updatePivotIfDirty($entity, $relation)
	{
		$key = $entity->getEntityKey();

		$original = $this->entity->getOriginalRelationships()[$relation]->find($key);

		if($entity->getPivotEntity()->getAttributes() !== $original->getPivotEntity()->getAttributes() )
		{
			$this->entityMap->$relation($this->entity)->updatePivot($entity);
		}	
	}

	/**
	 * Update Related Entities which attributes have
	 * been modified.
	 * 
	 * @return void
	 */
	protected function updateDirtyRelated()
	{
		$relations = $this->entityMap->getRelationships();
		$attributes = $this->getAttributes();

		foreach($relations as $relation)
		{
			if (! array_key_exists($relation, $attributes)) continue;

			$value = $attributes[$relation];

			if ($value == null) continue;

			if ($value instanceof ProxyInterface) continue;

			/*
			if ($value instanceof CollectionProxy)
			{
				// Implements partial updating
			}*/

			if ($value instanceof EntityCollection)
			{
				foreach($value as $entity)
				{
					$this->updateEntityIfDirty($entity);
				}
				continue;
			}
			if ($value instanceof Mappable)
			{
				$this->updateEntityIfDirty($value);
				continue;
			}
		}
	}

	/**
	 * Execute a store command on a dirty entity
	 * 
	 * @param  mixed $entity 
	 * @return void
	 */
	protected function updateEntityIfDirty($entity)
	{
		$mapper = Manager::mapper($entity);

		$checker = new StateChecker($entity, $mapper);

		$dirtyAttributes = $checker->getDirtyAttributes();

		if(count($dirtyAttributes) > 0)
		{
			$store = new Store($entity, $mapper, $this->query->newQuery());
			$store->execute();
		}
	}

	/**
	 * Execute an insert statement on the database
	 * 
	 * @return void
	 */
	protected function insert()
	{
		$entity = $this->entity;

		$attributes = $this->getRawAttributes();
		
		$id = $this->query->insertGetId($attributes);

		$keyName = $this->entityMap->getKeyName();
		
		$entity->$keyName = $id;
	}

	/**
	 * Get all the attributes of the entity that
	 * are not relationships.
	 * 
	 * @return array
	 */
	protected function getRawAttributes()
	{
		$attributes = $this->getAttributes();

		$relationships = $this->entityMap->getRelationships();

		return array_except($attributes, $relationships);
	}

	/**
	 * Set the proxies attribute on a freshly stored entity
	 * 
	 * @param \Analogue\ORM\Entity
	 */
	protected function setProxies($entity)
	{
		if(! $this->entityMap->relationsParsed() )
		{
			$initializer = new MapInitializer($this->mapper);
			$initializer->splitRelationsTypes($entity);
		}

		$attributes = $entity->getEntityAttributes();
		$singleRelations = $this->entityMap->getSingleRelationships();
		$manyRelations = $this->entityMap->getManyRelationships();

		$proxies = [];

		foreach($this->entityMap->getRelationships() as $relation)
		{
			if(! array_key_exists($relation, $attributes) || is_null($attributes[$relation]))
			{
				if (in_array($relation, $singleRelations))
				{
					$proxies[$relation] = new EntityProxy;
				}
				if (in_array($relation, $manyRelations))
				{	
					$proxies[$relation] = new CollectionProxy;
				}
			}
		}

		foreach($proxies as $key => $value)
		{	
			$entity->setEntityAttribute($key, $value);
		}

	}

	/**
	 * Run an update statement onthe entity
	 * 
	 * @return void
	 */
	protected function update()
	{
		$entity = $this->entity;

		$query = $this->query;

		$keyName = $this->entityMap->getKeyName();

		$query = $query->where($keyName, '=', $this->getId() );

		$dirtyAttributes = $this->entityState->getDirtyAttributes();
				
		if(count($dirtyAttributes) > 0) 
		{	
			$query->update($dirtyAttributes);
		}
	}

	
}