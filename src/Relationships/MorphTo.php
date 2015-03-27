<?php namespace Analogue\ORM\Relationships;

use Analogue\ORM\System\Mapper;
use Analogue\ORM\System\Manager;
use Analogue\ORM\EntityCollection;
use Illuminate\Support\Collection as BaseCollection;

class MorphTo extends BelongsTo {

	/**
	 * The type of the polymorphic relation.
	 *
	 * @var string
	 */
	protected $morphType;

	/**
	 * The entities whose relations are being eager loaded.
	 *
	 * @var \Analogue\ORM\EntityCollection
	 */
	protected $entities;

	/**
	 * All of the models keyed by ID.
	 *
	 * @var array
	 */
	protected $dictionary = array();

	/*
	 * Indicates if soft-deleted model instances should be fetched.
	 *
	 * @var bool
	 */
	protected $withTrashed = false;

	/**
	 * Indicate if the parent entity hold the key for the relation.
	 * 
	 * @var boolean
	 */
	protected static $ownForeignKey = true;

	/**
	 * Create a new belongs to relationship instance.
	 *
	 * @param  \Analogue\ORM\System\Query  $query
	 * @param  Mappable  $parent
	 * @param  string  $foreignKey
	 * @param  string  $otherKey
	 * @param  string  $type
	 * @param  string  $relation
	 * @return void
	 */
	public function __construct(Mapper $mapper, $parent, $foreignKey, $otherKey, $type, $relation)
	{
		$this->morphType = $type;

		parent::__construct($mapper, $parent, $foreignKey, $otherKey, $relation);
	}

	/**
	 * Set the constraints for an eager load of the relation.
	 *
	 * @param  array  $entities
	 * @return void
	 */
	public function addEagerConstraints(array $entities)
	{
		$this->buildDictionary($this->entities = EntityCollection::make($entities));
	}

	/**
	 * Build a dictionary with the entities
	 *
	 * @param  \Analogue\ORM\EntityCollection  $entities
	 * @return void
	 */
	protected function buildDictionary(EntityCollection $entities)
	{
		foreach ($entities as $entity)
		{
			if ($entity->getEntityAttribute($this->morphType) )
			{
				$this->dictionary[$entity->getEntityAttribute($this->morphType)][$entity->getEntityAttribute($this->foreignKey)][] = $entity;
			}
		}
	}

	/**
	 * Match the eagerly loaded results to their parents.
	 *
	 * @param  array   $entities
	 * @param  \Analogue\ORM\EntityCollection  $results
	 * @param  string  $relation
	 * @return array
	 */
	public function match(array $entities, EntityCollection $results, $relation)
	{
		return $entities;
	}

	/**
	 * Associate the model instance to the given parent.
	 *
	 * @param  $entity
	 * @return 
	 */
	public function associate($entity)
	{
		$this->parent->setEntityAttribute($this->foreignKey, $entity->getEntityKey());

		$this->parent->setEntityAttribute($this->morphType, $entity->getMorphClass());

		return $this->parent->setEntityAttribute($this->relation, $entity);
	}

	/*
	 * Get the results of the relationship.
	 *
	 * @return mixed
	 */
	public function getEager()
	{
		foreach (array_keys($this->dictionary) as $type)
		{
			$this->matchToMorphParents($type, $this->getResultsByType($type));
		}

		return $this->entities;
	}

	/**
	 * Match the results for a given type to their parents.
	 *
	 * @param  string  $type
	 * @param  \Analogue\ORM\EntityCollection  $results
	 * @return void
	 */
	protected function matchToMorphParents($type, EntityCollection $results)
	{
		foreach ($results as $result)
		{
			if (isset($this->dictionary[$type][$result->getEntityKey()]))
			{
				foreach ($this->dictionary[$type][$result->getEntityKey()] as $entity)
				{
					$entity->setEntityAttribute($this->relation, $result);
				}
			}
		}
	}

	/**
	 * Get all of the relation results for a type.
	 *
	 * @param  string  $type
	 * @return \Analogue\ORM\EntityCollection
	 */
	protected function getResultsByType($type)
	{
		$mapper = $this->relatedMapper->getManager()->mapper($type);

		$map = $mapper->getEntityMap();

		$key = $map->getKeyName();

		$query = $mapper->getQuery();

		//$query = $this->useWithTrashed($query);

		return $query->whereIn($key, $this->gatherKeysByType($type)->all())->get();
	}

	/**
	 * Gather all of the foreign keys for a given type.
	 *
	 * @param  string  $type
	 * @return array
	 */
	protected function gatherKeysByType($type)
	{
		$foreign = $this->foreignKey;

		return BaseCollection::make($this->dictionary[$type])->map(function($entities) use ($foreign)
		{
			return head($entities)->{$foreign};

		})->unique();
	}

	/**
	 * Get the dictionary used by the relationship.
	 *
	 * @return array
	 */
	public function getDictionary()
	{
		return $this->dictionary;
	}

}
