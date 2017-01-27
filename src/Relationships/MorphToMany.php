<?php

namespace Analogue\ORM\Relationships;

use Analogue\ORM\System\Mapper;
use Analogue\ORM\System\Query;

class MorphToMany extends BelongsToMany
{
    /**
     * The type of the polymorphic relation.
     *
     * @var string
     */
    protected $morphType;

    /**
     * The class name of the morph type constraint.
     *
     * @var string
     */
    protected $morphClass;

    /**
     * Indicates if we are connecting the inverse of the relation.
     *
     * This primarily affects the morphClass constraint.
     *
     * @var bool
     */
    protected $inverse;

    protected static $hasPivot = true;

    /**
     * Create a new has many relationship instance.
     *
     * @param Mapper               $mapper
     * @param \Analogue\ORM\Entity $parent
     * @param string               $name
     * @param string               $table
     * @param string               $foreignKey
     * @param string               $otherKey
     * @param string|null          $relationName
     * @param bool                 $inverse
     */
    public function __construct(Mapper $mapper, $parent, $name, $table, $foreignKey, $otherKey, $relationName = null, $inverse = false)
    {
        $this->inverse = $inverse;

        $this->morphType = $name.'_type';

        $this->morphClass = $inverse ? $mapper->getEntityMap()->getClass() : get_class($parent);

        parent::__construct($mapper, $parent, $table, $foreignKey, $otherKey, $relationName);
    }

    /**
     * Set the where clause for the relation query.
     *
     * @return self
     */
    protected function setWhere()
    {
        parent::setWhere();

        $this->query->where($this->table.'.'.$this->morphType, $this->morphClass);

        return $this;
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
        $query = parent::getRelationCountQuery($query, $parent);

        return $query->where($this->table.'.'.$this->morphType, $this->morphClass);
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

        $this->query->where($this->table.'.'.$this->morphType, $this->morphClass);
    }

    /**
     * Create a new pivot attachment record.
     *
     * @param int  $id
     * @param bool $timed
     *
     * @return array
     */
    protected function createAttachRecord($id, $timed)
    {
        $record = parent::createAttachRecord($id, $timed);

        return array_add($record, $this->morphType, $this->morphClass);
    }

    /**
     * Create a new query builder for the pivot table.
     *
     * @throws \InvalidArgumentException
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function newPivotQuery()
    {
        $query = parent::newPivotQuery();

        return $query->where($this->morphType, $this->morphClass);
    }

    /**
     * Create a new pivot model instance.
     *
     * @param array $attributes
     * @param bool  $exists
     *
     * @return Pivot
     */
    public function newPivot(array $attributes = [], $exists = false)
    {
        $pivot = new MorphPivot($this->parent, $this->parentMap, $attributes, $this->table, $exists);

        $pivot->setPivotKeys($this->foreignKey, $this->otherKey)
            ->setMorphType($this->morphType)
            ->setMorphClass($this->morphClass);

        return $pivot;
    }

    /**
     * Return Pivot attributes when available on a relationship.
     *
     * @return array
     */
    public function getPivotAttributes()
    {
        return $this->pivotColumns;
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
     * Get the class name of the parent model.
     *
     * @return string
     */
    public function getMorphClass()
    {
        return $this->morphClass;
    }
}
