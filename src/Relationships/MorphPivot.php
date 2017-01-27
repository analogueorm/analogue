<?php

namespace Analogue\ORM\Relationships;

use Analogue\ORM\System\Query;

class MorphPivot extends Pivot
{
    /**
     * The type of the polymorphic relation.
     *
     * Explicitly define this so it's not included in saved attributes.
     *
     * @var string
     */
    protected $morphType;

    /**
     * The value of the polymorphic relation.
     *
     * Explicitly define this so it's not included in saved attributes.
     *
     * @var string
     */
    protected $morphClass;

    /**
     * Set the keys for a save update query.
     *
     * @param Query $query
     *
     * @return Query
     */
    protected function setKeysForSaveQuery(Query $query)
    {
        $query->where($this->morphType, $this->morphClass);

        return parent::setKeysForSaveQuery($query);
    }

    /**
     * Set the morph type for the pivot.
     *
     * @param string $morphType
     *
     * @return self
     */
    public function setMorphType($morphType)
    {
        $this->morphType = $morphType;

        return $this;
    }

    /**
     * Set the morph class for the pivot.
     *
     * @param string $morphClass
     *
     * @return self
     */
    public function setMorphClass($morphClass)
    {
        $this->morphClass = $morphClass;

        return $this;
    }
}
