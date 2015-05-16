<?php namespace Analogue\ORM\Commands;

use Analogue\ORM\Mappable;
use Analogue\ORM\System\Manager;
use Analogue\ORM\EntityCollection;
use Analogue\ORM\System\EntityProxy;
use Analogue\ORM\System\StateChecker;
use Analogue\ORM\System\ProxyInterface;
use Analogue\ORM\System\MapInitializer;
use Analogue\ORM\System\CollectionProxy;
use Analogue\ORM\Exceptions\MappingException;

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
	 * @param  array $relations 
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

			if($value instanceof Mappable)
			{
				$this->createEntityIfNotExists($value);
			}
			
			// If the relation is a proxy, we test is the relation
			// has been lazy loaded, otherwise we'll just treat
			// the subset of newly added items.
			if ($value instanceof CollectionProxy && $value->isLoaded() )
			{
				$value = $value->getUnderlyingCollection();
			}

			if ($value instanceof CollectionProxy && ! $value->isLoaded() )
			{
				$value = $value->getAddedItems();
			}

			// If the relation's attribute is an array or a collection
			// let's assume the user intent is to store them as a many
			// relation, so we turn the array into an EntityCollection
			// if($value instanceof Collection || is_array($value) )

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
	 * @return Mappable|null
	 */
	protected function createEntityIfNotExists($entity)
	{
		$mapper = Manager::getMapper($entity);

		$checker = new StateChecker($entity, $mapper);

		if(! $checker->exists())
		{	
			return $mapper->store($entity);
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
			// If the key doesn't exist in the cache, it mean the relationship hasn't
			// be loaded either by eager or lazy loading.
			if(! array_key_exists($relation, $cachedAttributes)) continue;

			$value = $attributes[$relation];

			// Check if lazyloading proxy has been loaded.
			if($value instanceof ProxyInterface && ! $value->isLoaded()) continue;

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
				throw new MappingException("Store : couldn't interpret the value of $".$relation);
			}

			if(is_array($cachedValue) && count($cachedValue) > 0)
			{
				if (is_null($value))
				{
					$this->entityMap->$relation($this->entity)->detachMany($cachedValue);
					
					continue;
				}
				// If the collection is a proxy we have to check if it has been lazy loaded
				// then we need to retrieve the underlying collection
				if ($value instanceof CollectionProxy && $value->isLoaded() )
				{
					$value = $value->getUnderlyingCollection();
				}
				
				if ($value instanceof EntityCollection)
				{
					$hashes = $value->getEntityHashes();

					$missing = array_diff($cachedValue, $hashes);

					if(count($missing) > 0)
					{
						$this->entityMap->$relation($this->entity)->detachMany($missing);
					}

					continue;
				}
				throw new MappingException("Store : couldn't interpret the value of $".$relation);
			}
		}
	}

	protected function getEntityHash(Mappable $entity)
	{
		$mapper = Manager::getMapper($entity);

		$key = $mapper->getEntityMap()->getKeyName();

		return get_class($entity).'.'.$entity->getEntityAttribute($key);
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
			// Retrieve the value object from the entity's attributes
			$valueObject = $attributes[$localKey];

			// Unset the corresponding key
			unset($attributes[$localKey]);

			$valueObjectAttributes = $valueObject->getEntityAttributes();

			// Now (if setup in the entity map) we prefix the value object's
			// attributes with the snake_case name of the embedded class.
			$prefix = snake_case(class_basename($embed));

			foreach($valueObjectAttributes as $key=>$value)
			{
				$valueObjectAttributes[$prefix.'_'.$key] = $value;
				unset($valueObjectAttributes[$key]);
			}

			$attributes = array_merge($attributes, $valueObjectAttributes);
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

		$entityAttributes = $this->entity->getEntityAttributes();

		if (! array_key_exists($keyName, $entityAttributes))
		{
			return null;
		}
		else
		{
			return $entityAttributes[$keyName];
		}
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

			if ($attributes[$relation] instanceof CollectionProxy)
			{
				// If the collection is loaded we'll load the whole
				// underlying collection, if not, we'll only load
				// the freshly added Entities.
				if($attributes[$relation]->isLoaded() )
				{
					$value = $attributes[$relation]->getUnderlyingCollection();
				}
				else
				{
					$value = $attributes[$relation]->getAddedItems();
				}
			}
			else
			{
				$value = $attributes[$relation];
			}

			// We need to parse the related entities and compare
			// them to the key array we have in cache,which will
			// determine if we need to create a new pivot record
			$hashes = $value->getEntityHashes();
			
			if (array_key_exists($relation, $cachedAttributes))
			{
				// Compare the two
				$new = array_diff($hashes, array_keys($cachedAttributes[$relation]));
				$existing = array_intersect($hashes, array_keys($cachedAttributes[$relation])); 
			}
			else
			{
				$existing = [];
				$new = $hashes;
			}

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
					$this->updatePivotIfDirty($pivot, $relation);
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
		// tdd($entity);
		// $key = $entity->getEntityKey();

		// $original = $this->entity->getOriginalRelationships()[$relation]->find($key);

		// if($entity->getPivotEntity()->getAttributes() !== $original->getPivotEntity()->getAttributes() )
		// {
		// 	$this->entityMap->$relation($this->entity)->updatePivot($entity);
		// }	
		
		$attributes = $entity->getEntityAttributes();

		if(array_key_exists('pivot', $attributes))
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

			if ($value instanceof EntityProxy) continue;

			if ($value instanceof CollectionProxy && $value->isLoaded())
			{
				$value = $value->getUnderlyingCollection();
			}
			if ($value instanceof CollectionProxy && ! $value->isLoaded())
			{
				foreach($value->getAddedItems() as $entity)
				{
					$this->updateEntityIfDirty($entity);
				}
				continue;
			}

			if ($value instanceof EntityCollection)
			{
				foreach($value as $entity)
				{
					if (! $this->createEntityIfNotExists($entity))
					{
						$this->updateEntityIfDirty($entity);
					}
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
		$mapper = Manager::getMapper($entity);

		$checker = new StateChecker($entity, $mapper);

		$dirtyAttributes = $checker->getDirtyAttributes();

		if(count($dirtyAttributes) > 0)
		{
			$mapper->store($entity);
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
		
		$keyName = $this->entityMap->getKeyName();

		// Check if the primary key is defined in the attributes
		if(array_key_exists($keyName, $attributes))
		{
			$this->query->insert($attributes);
		}	
		else
		{
			$id = $this->query->insertGetId($attributes);

			$entity->setEntityAttribute($keyName, $id);
		}
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
			$initializer = new MapInitializer($this->mapper->getEntityMap());
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
					$proxies[$relation] = new EntityProxy($entity, $relation);
				}
				if (in_array($relation, $manyRelations))
				{	
					$proxies[$relation] = new CollectionProxy($entity, $relation);
				}
			}
		}

		foreach($proxies as $key => $value)
		{	
			$entity->setEntityAttribute($key, $value);
		}

	}

	/**
	 * Run an update statement on the entity
	 * 
	 * @return void
	 */
	protected function update()
	{
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
