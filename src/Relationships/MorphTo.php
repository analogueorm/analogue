<?php

namespace Analogue\ORM\Relationships;

use Analogue\ORM\EntityCollection;
use Analogue\ORM\System\Mapper;
use Illuminate\Support\Collection as BaseCollection;

class MorphTo extends BelongsTo
{
    /**
     * The type of the polymorphic relation.
     *
     * @var string
     */
    protected $morphType;

    /**
     * The entities whose relations are being eager loaded.
     *
     * @var EntityCollection
     */
    protected $entities;

    /**
     * All of the models keyed by ID.
     *
     * @var array
     */
    protected $dictionary = [];

    /**
     * Indicates if soft-deleted model instances should be fetched.
     *
     * @var bool
     */
    protected $withTrashed = false;

    /**
     * Indicate if the parent entity hold the key for the relation.
     *
     * @var bool
     */
    protected static $ownForeignKey = true;

    /**
     * Create a new belongs to relationship instance.
     *
     * @param Mapper                 $mapper
     * @param \Analogue\ORM\Mappable $parent
     * @param string                 $foreignKey
     * @param string                 $otherKey
     * @param string                 $type
     * @param string                 $relation
     */
    public function __construct(Mapper $mapper, $parent, $foreignKey, $otherKey, $type, $relation)
    {
        $this->morphType = $type;

        parent::__construct($mapper, $parent, $foreignKey, $otherKey, $relation);
    }

    /**
     * Set the constraints for an eager load of the relation.
     *
     * @param array $entities
     *
     * @return void
     */
    public function addEagerConstraints(array $entities)
    {
        $this->buildDictionary($this->entities = EntityCollection::make($entities));
    }

    /**
     * Build a dictionary with the entities.
     *
     * @param EntityCollection $entities
     *
     * @return void
     */
    protected function buildDictionary(EntityCollection $entities)
    {
        foreach ($entities as $entity) {
            if ($entity->getEntityAttribute($this->morphType)) {
                $foreign = $this->foreignKey;
                $foreign = $this->relatedMap->getAttributeNameForColumn($foreign);
                $this->dictionary[$entity->getEntityAttribute($this->morphType)][$entity->getEntityAttribute($foreign)][] = $entity;
            }
        }
    }

    /**
     * Match the eagerly loaded results to their parents.
     *
     * @param array            $entities
     * @param EntityCollection $results
     * @param string           $relation
     *
     * @return array
     */
    public function match(array $entities, EntityCollection $results, $relation)
    {
        return $entities;
    }

    /**
     * Get the results of the relationship.
     *
     * @throws \Analogue\ORM\Exceptions\MappingException
     *
     * @return EntityCollection
     */
    public function getEager()
    {
        foreach (array_keys($this->dictionary) as $type) {
            $this->matchToMorphParents($type, $this->getResultsByType($type));
        }

        return $this->entities;
    }

    /**
     * Match the results for a given type to their parents.
     *
     * @param string           $type
     * @param EntityCollection $results
     *
     * @return void
     */
    protected function matchToMorphParents($type, EntityCollection $results)
    {
        $mapper = $this->relatedMapper->getManager()->mapper($type);
        $keyName = $mapper->getEntityMap()->getKeyName();

        foreach ($results as $result) {
            $key = $result->{$keyName};

            if (isset($this->dictionary[$type][$key])) {
                foreach ($this->dictionary[$type][$key] as $entity) {
                    $entity->setEntityAttribute($this->relation, $result);
                }
            }
        }
    }

    /**
     * Get all of the relation results for a type.
     *
     * @param string $type
     *
     * @throws \Analogue\ORM\Exceptions\MappingException
     *
     * @return EntityCollection
     */
    protected function getResultsByType($type)
    {
        $mapper = $this->relatedMapper->getManager()->mapper($type);

        $key = $mapper->getEntityMap()->getKeyName();

        $query = $mapper->getQuery();

        return $query->whereIn($key, $this->gatherKeysByType($type)->all())->get();
    }

    /**
     * Gather all of the foreign keys for a given type.
     *
     * @param string $type
     *
     * @return BaseCollection
     */
    protected function gatherKeysByType($type)
    {
        $foreign = $this->foreignKey;

        return BaseCollection::make($this->dictionary[$type])->map(function ($entities) use ($foreign) {
            return head($entities)->{$foreign};
        })->unique();
    }

    /**
     * Associate the model instance to the given parent.
     *
     * @param mixed $entity
     *
     * @return void
     */
    public function associate($entity)
    {
        // The Mapper will retrieve this association within the object model, we won't be using
        // the foreign key attribute inside the parent Entity.
        //
        //$this->parent->setEntityAttribute($this->foreignKey, $entity->getEntityAttribute($this->otherKey));
        //
        // Instead, we'll just add the object to the Entity's attribute

        $this->parent->setEntityAttribute($this->relation, $entity->getEntityObject());
    }

    /**
     * Get the foreign key value pair for a related object.
     *
     * @var mixed
     *
     * @return array
     */
    public function getForeignKeyValuePair($related)
    {
        $foreignKey = $this->getForeignKey();

        if ($related) {
            $wrapper = $this->factory->make($related);

            $relatedKey = $this->relatedMap->getKeyName();

            return [
                $foreignKey      => $wrapper->getEntityAttribute($relatedKey),
                $this->morphType => $wrapper->getMap()->getMorphClass(),
            ];
        } else {
            return [$foreignKey => null];
        }
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
