<?php namespace Analogue\ORM\Commands;

use Analogue\ORM\Mappable;
use Analogue\ORM\System\Mapper;
use Analogue\ORM\System\Manager;
use Analogue\ORM\EntityCollection;
//use Analogue\ORM\System\StateChecker;
use Analogue\ORM\Drivers\QueryAdapter;
use Analogue\ORM\System\Aggregate;
use Analogue\ORM\System\InternallyMappable;
use Analogue\ORM\System\Proxies\EntityProxy;
use Analogue\ORM\Exceptions\MappingException;
use Analogue\ORM\System\Proxies\ProxyInterface;
use Analogue\ORM\System\Proxies\CollectionProxy;

/**
 * Persist entities & relationships to the
 * database.
 */
class Store 
{
	/**
	 * Aggregated Entity
	 * 
	 * @var Analogue\ORM\System\Aggregate
	 */
	protected $aggregate;

	/**
	 * Query Builder
	 * 
	 * @var Analogue\ORM\Drivers\QueryAdapter
	 */
	protected $query;

	public function __construct(Aggregate $aggregate, QueryAdapter $query)
	{
		$this->aggregate = $aggregate;

		$this->query = $query->from($aggregate->getEntityMap()->getTable());
	}

	/**
	 * Persist the entity in the database
	 * 
	 * @return void
	 */
	public function execute()
	{
		$entity = $this->aggregate->getEntityObject();

		$mapper = $this->aggregate->getMapper();

		if ($mapper->fireEvent('storing', $entity) === false)
		{
			return false;
		}

		$this->preStoreProcess();

		/**
		 * We will test the entity for existence
		 * and run a creation if it doesn't exists
		 */
		if (! $this->aggregate->exists() ) 
		{
			if ($mapper->fireEvent('creating', $entity) === false)
			{
				return false;
			}
			
			$this->insert();

			$mapper->fireEvent('created', $entity, false);
		}
		
		/**
		 * We'll only run an update if the entity
		 * is actually dirty
		 */
		if ($this->aggregate->isDirty() )
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

		return $entity;;
	}

	/**
	 * Run all operations that have to occur before actually
	 * storing the entity
	 * 
	 * @return void
	 */
	protected function preStoreProcess()
	{
		// Create any related object that doesn't exist in the database.
		$localRelationships = $this->aggregate->getEntityMap()->getLocalRelationships();
		
		$this->createRelatedEntities($localRelationships);

		// As storing the entity will reset the original relationships in the EntityCache
		// we must parse for removed relations before storing it.
		$this->detachMissingRelations();
	}

	/**
	 * Check for existence and create non-existing related entities
	 * 
	 * @param  array
	 * @return void
	 */
	protected function createRelatedEntities($relations)
	{
		$entitiesToCreate = $this->aggregate->getNonExistingRelated($relations);
				
		foreach($entitiesToCreate as $aggregate)
		{
			$this->createStoreCommand($aggregate)->execute();
		}
	}

	/**
	 * Create a new store command
	 * 
	 * @param  Aggregate $aggregate 
	 * @return void
	 */
	protected function createStoreCommand(Aggregate $aggregate)
	{
		// We gotta retrieve the corresponding query adapter to use.
		$mapper = $aggregate->getMapper();

		return new Store($aggregate, $mapper->newQueryBuilder() );
	}

	/**
	 * Run all operations that have to occur after the entity 
	 * is stored.
	 * 
	 * @return void
	 */
	protected function postStoreProcess()
	{
		// Create any related object that doesn't exist in the database.
		$foreignRelationships = $this->aggregate->getEntityMap()->getForeignRelationships();
		$this->createRelatedEntities($foreignRelationships);

		// Update any pivot tables that has been modified.
		$this->aggregate->updatePivotRecords();

		// Update any dirty relationship
		$this->updateDirtyRelated();

		if(count($this->entityMap->getRelationships()) >0)
		{
			$this->setProxies($this->entity);
		}

		// Update Entity Cache
		$this->aggregate->getMapper()->getEntityCache()->refresh($this->entity);
	}

	/**
	 * Check the cache for missing relationships and run necessary
	 * operations if needed. 
	 * 
	 * @return void
	 */
	protected function detachMissingRelations()
	{

	}

	/**
	 * Detach any relation that have been removed
	 * from the entity.
	 * 
	 * @return void
	 */
	/*protected function detachMissingRelations()
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
						$this->entityMap->$relation($this->entity->getObject())->detachMany($missing);
					}

					continue;
				}
				throw new MappingException("Store : couldn't interpret the value of $".$relation);
			}
		}
	}*/


	/**
	 * Parse Many-to-many relationships and create pivot records
	 * if needed
	 * 
	 * @param  array  $relations 
	 * @return void
	 */
	/*protected function updatePivotRelations(array $relations)
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
			
			//if(! is_array($hashes)) tdd($value);

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

				$this->entityMap->$relation($this->entity->getObject() )->createPivots($pivots);
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

	}*/

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
			$this->entityMap->$relation($this->entity->getObject() )->updatePivot($entity);
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

	/*/**
	 * Get a stateChecker object instance
	 * 
	 * @param  mixed $entity 
	 * @return \Analogue\ORM\System\StateChecker
	 */
	/*protected function getStateChecker($entity)
	{
		$mapper = Manager::getMapper($entity);

		$wrappedEntity = $this->wrapperFactory->make($entity);

		$checker = new StateChecker($wrappedEntity, $mapper);

		return $checker;
	}*/

	/**
	 * Execute a store command on a dirty entity
	 * 
	 * @param  mixed $entity 
	 * @return void
	 */
	protected function updateEntityIfDirty($entity)
	{
		$mapper = Manager::getMapper($entity);

		$checker = $this->getStateChecker($entity);

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
		$aggregate = $this->aggregate;

		$attributes = $aggregate->getRawAttributes();
		
		$keyName = $aggregate->getEntityMap()->getKeyName();

		// Check if the primary key is defined in the attributes
		if(array_key_exists($keyName, $attributes) && $attributes[$keyName] != null)
		{
			$this->query->insert($attributes);
		}	
		else
		{
			$sequence = $aggregate->getEntityMap()->getSequence();

			$id = $this->query->insertGetId($attributes, $sequence);

			$aggregate->setEntityAttribute($keyName, $id);
		}
	}

	/**
	 * Set the proxies attribute on a freshly stored entity
	 * 
	 * @param InternallyMappable $entity
	 */
	protected function setProxies(InternallyMappable $entity)
	{
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
					$proxies[$relation] = new EntityProxy($entity->getObject(), $relation);
				}
				if (in_array($relation, $manyRelations))
				{	
					$proxies[$relation] = new CollectionProxy($entity->getObject(), $relation);
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

		$keyName = $this->aggregate->getEntityKey();

		$query = $query->where($keyName, '=', $this->aggregate->getEntityId() );

		$dirtyAttributes = $this->aggregate->getDirtyRawAttributes();
				
		if(count($dirtyAttributes) > 0) 
		{	
			$query->update($dirtyAttributes);
		}
	}
}
