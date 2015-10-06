<?php namespace Analogue\ORM\System;

/**
 * This class is intended to facilitate the handling of ManyToMany relationships
 * inside the cache
 */
class CachedRelationship
{

    /**
     * The Hash of the related entity
     *
     * @var string
     */
    protected $hash;

    /**
     * Pivot attributes, if any
     *
     * @var array
     */
    protected $pivotAttributes;

    public function __construct($hash, $pivotAttributes = [])
    {
        $this->hash = $hash;
        $this->pivotAttributes = $pivotAttributes;
    }

    /**
     * Return true if any pivot attributes are present
     *
     * @return boolean
     */
    public function hasPivotAttributes()
    {
        return count($this->pivotAttributes) > 0 ? true : false;
    }

    /**
     * Returns the hash of the related entity
     *
     * @return string
     */
    public function getHash()
    {
        return $this->hash;
    }

    /**
     * Get the cached values for the pivot attributes
     *
     * @return array
     */
    public function getPivotAttributes()
    {
        return $this->pivotAttributes;
    }

    /**
     * Access to the hash for fast cache comparison
     *
     * @return string
     */
    public function __toString()
    {
        return $this->hash;
    }
}
