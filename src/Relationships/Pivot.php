<?php

namespace Analogue\ORM\Relationships;

use Analogue\ORM\Entity;

class Pivot extends Entity
{
    /**
     * @var bool
     */
    protected $exists;

    /**
     * Pivot's table.
     *
     * @var string
     */
    protected $table;

    /**
     * The parent entity of the relationship.
     *
     * @var object
     */
    protected $parent;

    /**
     * The parent entity of the relationship.
     *
     * @var \Analogue\ORM\EntityMap
     */
    protected $parentMap;

    /**
     * We may define a pivot mapping to deal with
     * soft deletes, timestamps, etc.
     *
     * @var \Analogue\ORM\EntityMap
     */
    protected $pivotMap;

    /**
     * The name of the foreign key column.
     *
     * @var string
     */
    protected $foreignKey;

    /**
     * The name of the "other key" column.
     *
     * @var string
     */
    protected $otherKey;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Pivot uses timestamps ?
     *
     * @var bool
     */
    protected $timestamps;

    /**
     * Create a new pivot model instance.
     *
     * @param \Analogue\ORM\System\InternallyMappable $parent
     * @param \Analogue\ORM\EntityMap                 $parentMap
     * @param array                                   $attributes
     * @param string                                  $table
     * @param bool                                    $exists
     */
    public function __construct($parent, $parentMap, $attributes, $table, $exists = false)
    {
        // The pivot model is a "dynamic" model since we will set the tables dynamically
        // for the instance. This allows it work for any intermediate tables for the
        // many to many relationship that are defined by this developer's classes.
        $this->setEntityAttributes($attributes);

        $this->table = $table;

        // We store off the parent instance so we will access the timestamp column names
        // for the model, since the pivot model timestamps aren't easily configurable
        // from the developer's point of view. We can use the parents to get these.
        $this->parent = $parent;

        $this->parentMap = $parentMap;

        $this->exists = $exists;

        $this->timestamps = $this->hasTimestampAttributes();
    }

    /**
     * Get the foreign key column name.
     *
     * @return string
     */
    public function getForeignKey()
    {
        return $this->foreignKey;
    }

    /**
     * Get the "other key" column name.
     *
     * @return string
     */
    public function getOtherKey()
    {
        return $this->otherKey;
    }

    /**
     * Set the key names for the pivot model instance.
     *
     * @param string $foreignKey
     * @param string $otherKey
     *
     * @return $this
     */
    public function setPivotKeys($foreignKey, $otherKey)
    {
        $this->foreignKey = $foreignKey;

        $this->otherKey = $otherKey;

        return $this;
    }

    /**
     * Determine if the pivot model has timestamp attributes.
     *
     * @return bool
     */
    public function hasTimestampAttributes()
    {
        return array_key_exists($this->getCreatedAtColumn(), $this->attributes);
    }

    /**
     * Get the name of the "created at" column.
     *
     * @return string
     */
    public function getCreatedAtColumn()
    {
        return $this->parentMap->getCreatedAtColumn();
    }

    /**
     * Get the name of the "updated at" column.
     *
     * @return string
     */
    public function getUpdatedAtColumn()
    {
        return $this->parentMap->getUpdatedAtColumn();
    }
}
