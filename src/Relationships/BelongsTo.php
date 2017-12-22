<?php

namespace Analogue\ORM\Relationships;

use Analogue\ORM\Exceptions\MappingException;
use Analogue\ORM\Mappable;
use Analogue\ORM\System\Mapper;
use Analogue\ORM\System\Query;
use Illuminate\Database\Query\Expression;

class BelongsTo extends Relationship
{
    /**
     * The foreign key of the parent model.
     *
     * @var string
     */
    protected $foreignKey;

    /**
     * The associated key on the parent model.
     *
     * @var string
     */
    protected $otherKey;

    /**
     * The name of the relationship.
     *
     * @var string
     */
    protected $relation;

    /**
     * Create a new belongs to relationship instance.
     *
     * @param Mapper   $mapper
     * @param Mappable $parent
     * @param string   $foreignKey
     * @param string   $otherKey
     * @param string   $relation
     */
    public function __construct(Mapper $mapper, $parent, $foreignKey, $otherKey, $relation)
    {
        $this->otherKey = $otherKey;
        $this->relation = $relation;
        $this->foreignKey = $foreignKey;

        parent::__construct($mapper, $parent);
    }

    /**
     * Get the results of the relationship.
     *
     * @param  $relation
     *
     * @return \Analogue\ORM\Entity
     */
    public function getResults($relation)
    {
        $result = $this->query->first();

        $this->cacheRelation($result, $relation);

        return $result;
    }

    /**
     * Set the base constraints on the relation query.
     *
     * @return void
     */
    public function addConstraints()
    {
        if (static::$constraints) {
            // For belongs to relationships, which are essentially the inverse of has one
            // or has many relationships, we need to actually query on the primary key
            // of the related models matching on the foreign key that's on a parent.
            $this->query->where($this->otherKey, '=', $this->parent->getEntityAttribute($this->foreignKey));
        }
    }

    /**
     * Add the constraints for a relationship count query.
     *
     * @param Query $query
     * @param Query $parent
     *
     * @return Query
     */
    public function getRelationCountQuery(Query $query, Query $parent)
    {
        $query->select(new Expression('count(*)'));

        $otherKey = $this->wrap($query->getTable().'.'.$this->otherKey);

        return $query->where($this->getQualifiedForeignKey(), '=', new Expression($otherKey));
    }

    /**
     * Set the constraints for an eager load of the relation.
     *
     * @param array $results
     *
     * @return void
     */
    public function addEagerConstraints(array $results)
    {
        // We'll grab the primary key name of the related models since it could be set to
        // a non-standard name and not "id". We will then construct the constraint for
        // our eagerly loading query so it returns the proper models from execution.
        $key = $this->otherKey;

        $this->query->whereIn($key, $this->getEagerModelKeys($results));
    }

    /**
     * Gather the keys from an array of related models.
     *
     * @param array $results
     *
     * @return array
     */
    protected function getEagerModelKeys(array $results)
    {
        $keys = [];

        // First we need to gather all of the keys from the result set so we know what
        // to query for via the eager loading query. We will add them to an array then
        // execute a "where in" statement to gather up all of those related records.
        foreach ($results as $result) {
            if (array_key_exists($this->foreignKey, $result) && !is_null($value = $result[$this->foreignKey])) {
                $keys[] = $value;
            }
        }

        // If there are no keys that were not null we will just return an array with 0 in
        // it so the query doesn't fail, but will not return any results, which should
        // be what this developer is expecting in a case where this happens to them.
        if (count($keys) == 0) {
            return [0];
        }

        return array_values(array_unique($keys));
    }

    /**
     * Match the Results array to an eagerly loaded relation.
     *
     * @param array  $results
     * @param string $relation
     *
     * @return array
     */
    public function match(array $results, $relation)
    {
        $foreign = $this->foreignKey;

        $other = $this->otherKey;

        // Execute the relationship and get related entities as an EntityCollection
        $entities = $this->getEager();

        // First we will get to build a dictionary of the child models by their primary
        // key of the relationship, then we can easily match the children back onto
        // the parents using that dictionary and the primary key of the children.
        $dictionary = [];

        // TODO ; see if otherKey is the primary key of the related entity, we can
        // simply use the EntityCollection key to match entities to results, which
        // will be much more efficient, and use this method as a fallback if the
        // otherKey is not the same as the primary Key.
        foreach ($entities as $entity) {
            $entity = $this->factory->make($entity);
            $dictionary[$entity->getEntityAttribute($other)] = $entity->getObject();
        }

        // Once we have the dictionary constructed, we can loop through all the parents
        // and match back onto their children using these keys of the dictionary and
        // the primary key of the children to map them onto the correct instances.
        return array_map(function ($result) use ($dictionary, $foreign, $relation) {
            if (array_key_exists($foreign, $result) && isset($dictionary[$result[$foreign]])) {
                $result[$relation] = $dictionary[$result[$foreign]];
            } else {
                $result[$relation] = null;
            }

            return $result;
        }, $results);
    }

    public function sync(array $entities)
    {
        if (count($entities) > 1) {
            throw new MappingException("Single Relationship shouldn't be synced with more than one entity");
        }

        if (count($entities) == 1) {
            return $this->associate($entities[0]);
        }

        return false;
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
        $this->parent->setEntityAttribute($this->foreignKey, $entity->getEntityAttribute($this->otherKey));
    }

    /**
     * Dissociate previously associated model from the given parent.
     *
     * @return Mappable
     */
    public function dissociate()
    {
        // The Mapper will retrieve this association within the object model, we won't be using
        // the foreign key attribute inside the parent Entity.
        //
        //$this->parent->setEntityAttribute($this->foreignKey, null);

        $this->parent->setEntityAttribute($this->relation, null);
    }

    /**
     * Get the foreign key of the relationship.
     *
     * @return string
     */
    public function getForeignKey()
    {
        return $this->foreignKey;
    }

    /**
     * Get the foreign key value pair for a related object.
     *
     * @param mixed $related
     *
     * @return array
     */
    public function getForeignKeyValuePair($related)
    {
        $foreignKey = $this->getForeignKey();

        if ($related) {
            $wrapper = $this->factory->make($related);

            $relatedKey = $this->relatedMap->getKeyName();

            return [$foreignKey => $wrapper->getEntityAttribute($relatedKey)];
        } else {
            return [$foreignKey => null];
        }
    }

    /**
     * Get the fully qualified foreign key of the relationship.
     *
     * @return string
     */
    public function getQualifiedForeignKey()
    {
        return $this->parentMap->getTable().'.'.$this->foreignKey;
    }

    /**
     * Get the associated key of the relationship.
     *
     * @return string
     */
    public function getOtherKey()
    {
        return $this->otherKey;
    }

    /**
     * Get the fully qualified associated key of the relationship.
     *
     * @return string
     */
    public function getQualifiedOtherKeyName()
    {
        return $this->relatedMap->getTable().'.'.$this->otherKey;
    }
}
