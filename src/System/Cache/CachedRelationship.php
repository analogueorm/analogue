<?php

namespace Analogue\ORM\System\Cache;

/**
 * This class is intended to facilitate the handling of ManyToMany relationships
 * inside the cache.
 */
class CachedRelationship
{
    /**
     * The Hash of the related entity.
     *
     * @var string
     */
    protected $hash;

    /**
     * Pivot attributes.
     *
     * @var array
     */
    protected $pivotAttributes;

    /**
     * CachedRelationship constructor.
     *
     * @param string $hash
     * @param array  $pivotAttributes
     */
    public function __construct(string $hash, array $pivotAttributes = [])
    {
        $this->hash = $hash;
        $this->pivotAttributes = $pivotAttributes;
    }

    /**
     * Return true if any pivot attributes are present.
     *
     * @return bool
     */
    public function hasPivotAttributes(): bool
    {
        return !empty($this->pivotAttributes);
    }

    /**
     * Returns the hash of the related entity.
     *
     * @return string
     */
    public function getHash(): string
    {
        return $this->hash;
    }

    /**
     * Get the cached values for the pivot attributes.
     *
     * @return array
     */
    public function getPivotAttributes(): array
    {
        return $this->pivotAttributes;
    }

    /**
     * Access to the hash for fast cache comparison.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->hash;
    }
}
