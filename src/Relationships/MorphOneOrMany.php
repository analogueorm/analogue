<?php

namespace Analogue\ORM\Relationships;

use Analogue\ORM\System\Mapper;
use Analogue\ORM\System\Query;

abstract class MorphOneOrMany extends HasOneOrMany
{
    /**
     * The foreign key type for the relationship.
     *
     * @var string
     */
    protected $morphType;

    /**
     * The class name of the parent model.
     *
     * @var string
     */
    protected $morphClass;

    /**
     * Create a new has many relationship instance.
     *
     * @param Mapper                 $mapper
     * @param \Analogue\ORM\Mappable $parent
     * @param string                 $type
     * @param string                 $id
     * @param string                 $localKey
     *
     * @throws \Analogue\ORM\Exceptions\MappingException
     */
    public function __construct(Mapper $mapper, $parent, $type, $id, $localKey)
    {
        $this->morphType = $type;
        $this->morphClass = $mapper->getManager()->getMapper($parent)->getEntityMap()->getMorphClass();

        parent::__construct($mapper, $parent, $id, $localKey);
    }

    /**
     * Set the base constraints on the relation query.
     *
     * @return void
     */
    public function addConstraints()
    {
        if (static::$constraints) {
            parent::addConstraints();

            $this->query->where($this->morphType, $this->morphClass);
        }
    }

    /**
     * Get the relationship count query.
     *
     * @param Query $query
     * @param Query $parent
     *
     * @return Query
     */
    public function getRelationCountQuery(Query $query, Query $parent)
    {
        $query = parent::getRelationCountQuery($query, $parent);

        return $query->where($this->morphType, $this->morphClass);
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
        parent::addEagerConstraints($results);

        $this->query->where($this->morphType, $this->morphClass);
    }

    /**
     * Get the foreign key "type" name.
     *
     * @return string
     */
    public function getMorphType()
    {
        return $this->morphType;
    }

    /**
     * Get the plain morph type name without the table.
     *
     * @return string
     */
    public function getPlainMorphType()
    {
        return last(explode('.', $this->morphType));
    }

    /**
     * Get the class name of the parent model.
     *
     * @return string
     */
    public function getMorphClass()
    {
        return $this->morphClass;
    }

    /**
     * Get the foreign key as value pair for this relation.
     *
     * @return array
     */
    public function getForeignKeyValuePair()
    {
        return [
            $this->getPlainForeignKey() => $this->getParentKey(),
            $this->getPlainMorphType()  => $this->morphClass,
        ];
    }
}
