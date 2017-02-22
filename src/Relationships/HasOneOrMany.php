<?php

namespace Analogue\ORM\Relationships;

use Analogue\ORM\EntityCollection;
use Analogue\ORM\System\Mapper;

abstract class HasOneOrMany extends Relationship
{
    /**
     * The foreign key of the parent model.
     *
     * @var string
     */
    protected $foreignKey;

    /**
     * The local key of the parent model.
     *
     * @var string
     */
    protected $localKey;

    /**
     * Create a new has many relationship instance.
     *
     * @param Mapper                 $mapper
     * @param \Analogue\ORM\Mappable $parentEntity
     * @param string                 $foreignKey
     * @param string                 $localKey
     */
    public function __construct(Mapper $mapper, $parentEntity, $foreignKey, $localKey)
    {
        $this->localKey = $localKey;
        $this->foreignKey = $foreignKey;

        parent::__construct($mapper, $parentEntity);
    }

    /**
     * @param \Analogue\ORM\Entity $entity
     */
    public function attachOne($entity)
    {
        $wrapper = $this->factory->make($entity);

        // Ok, we need to guess the inverse of the relation from there.
        // Let's assume the inverse of the relation method is the name of
        // the entity.

        $wrapper->setEntityAttribute($this->getPlainForeignKey(), $this->getParentKey());
    }

    /**
     * @param EntityCollection $entities
     */
    public function attachMany(EntityCollection $entities)
    {
        foreach ($entities as $entity) {
            $this->attachOne($entity);
        }
    }

    /**
     * @param $entityHash
     */
    protected function detachOne($entityHash)
    {
        $this->detachMany([$entityHash]);
    }

    /**
     * Attach ids that are passed as arguments, and detach any other.
     *
     * @param mixed $entities
     *
     * @throws \InvalidArgumentException
     *
     * @return void
     */
    public function sync(array $entities)
    {
        $this->detachExcept($entities);
    }

    /**
     * @param  $entities
     *
     * @throws \InvalidArgumentException
     */
    protected function detachExcept($entities)
    {
        $query = $this->query->getQuery()->from($this->relatedMap->getTable());

        if (count($entities) > 0) {
            $keys = $this->getKeys($entities);
            $query->whereNotIn($this->relatedMap->getKeyName(), $keys);
        }

        $parentKey = $this->parentMap->getKeyName();

        $query->where($this->getPlainForeignKey(), '=', $this->parent->getEntityAttribute($parentKey))
            ->update([$this->getPlainForeignKey() => null]);
    }

    /**
     * @param array $entityHashes
     */
    public function detachMany(array $entityHashes)
    {
        $keys = [];

        foreach ($entityHashes as $hash) {
            $split = explode('.', $hash);
            $keys[] = $split[1];
        }

        $query = $this->query->getQuery()->from($this->relatedMap->getTable());

        $query->whereIn($this->relatedMap->getKeyName(), $keys)
            ->update([$this->getPlainForeignKey() => null]);
    }

    /**
     * Set the base constraints on the relation query.
     *
     * @return void
     */
    public function addConstraints()
    {
        if (static::$constraints) {
            $this->query->where($this->foreignKey, '=', $this->getParentKey());
        }
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
        $this->query->whereIn($this->foreignKey, $this->getKeys($entities, $this->localKey));
    }

    /**
     * Match the eagerly loaded results to their single parents.
     *
     * @param array            $entities
     * @param EntityCollection $results
     * @param string           $relation
     *
     * @return array
     */
    public function matchOne(array $entities, EntityCollection $results, $relation)
    {
        return $this->matchOneOrMany($entities, $results, $relation, 'one');
    }

    /**
     * Match the eagerly loaded results to their many parents.
     *
     * @param array            $entities
     * @param EntityCollection $results
     * @param string           $relation
     *
     * @return array
     */
    public function matchMany(array $entities, EntityCollection $results, $relation)
    {
        return $this->matchOneOrMany($entities, $results, $relation, 'many');
    }

    /**
     * Match the eagerly loaded results to their many parents.
     *
     * @param array            $entities
     * @param EntityCollection $results
     * @param string           $relation
     * @param string           $type
     *
     * @return array
     */
    protected function matchOneOrMany(array $entities, EntityCollection $results, $relation, $type)
    {
        $dictionary = $this->buildDictionary($results);

        $cache = $this->parentMapper->getEntityCache();

        // Once we have the dictionary we can simply spin through the parent models to
        // link them up with their children using the keyed dictionary to make the
        // matching very convenient and easy work. Then we'll just return them.
        foreach ($entities as $entity) {
            $entity = $this->factory->make($entity);

            $key = $entity->getEntityAttribute($this->localKey);

            if (isset($dictionary[$key])) {
                $value = $this->getRelationValue($dictionary, $key, $type);

                $entity->setEntityAttribute($relation, $value);

                $cache->cacheLoadedRelationResult($entity, $relation, $value, $this);
            }
        }

        return $entities;
    }

    /**
     * Get the value of a relationship by one or many type.
     *
     * @param array  $dictionary
     * @param string $key
     * @param string $type
     *
     * @return mixed
     */
    protected function getRelationValue(array $dictionary, $key, $type)
    {
        $value = $dictionary[$key];

        return $type == 'one' ? reset($value) : $this->relatedMap->newCollection($value);
    }

    /**
     * Build model dictionary keyed by the relation's foreign key.
     *
     * @param EntityCollection $results
     *
     * @return array
     */
    protected function buildDictionary(EntityCollection $results)
    {
        $dictionary = [];

        $foreign = $this->getPlainForeignKey();

        $foreign = $this->relatedMap->getAttributeNameForColumn($foreign);

        // First we will create a dictionary of models keyed by the foreign key of the
        // relationship as this will allow us to quickly access all of the related
        // models without having to do nested looping which will be quite slow.
        foreach ($results as $result) {
            $dictionary[$result->{$foreign}][] = $result;
        }

        return $dictionary;
    }

    /**
     * Get the key for comparing against the parent key in "has" query.
     *
     * @return string
     */
    public function getHasCompareKey()
    {
        return $this->getForeignKey();
    }

    /**
     * Get the foreign key for the relationship.
     *
     * @return string
     */
    public function getForeignKey()
    {
        return $this->foreignKey;
    }

    /**
     * Get the plain foreign key.
     *
     * @return string
     */
    public function getPlainForeignKey()
    {
        $segments = explode('.', $this->getForeignKey());

        return $segments[count($segments) - 1];
    }

    /**
     * Get the key value of the parent's local key.
     *
     * @return mixed
     */
    public function getParentKey()
    {
        return $this->parent->getEntityAttribute($this->localKey);
    }

    /**
     * Get the fully qualified parent key name.
     *
     * @return string
     */
    public function getQualifiedParentKeyName()
    {
        return $this->parentMap->getTable().'.'.$this->localKey;
    }

    /**
     * Get the foreign key as value pair for this relation.
     *
     * @return array
     */
    public function getForeignKeyValuePair()
    {
        return [$this->getPlainForeignKey() => $this->getParentKey()];
    }
}
