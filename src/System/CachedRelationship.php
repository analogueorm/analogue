<?php namespace Analogue\ORM\System;

class CachedRelationship {

    protected $hash;

    protected $pivotAttributes;

    public function __construct($hash, $pivotAttributes = [])
    {
        $this->hash = $hash;
        $this->pivotAttributes = $pivotAttributes;
    }

    public function hasPivotAttributes()
    {
        return count($this->pivotAttributes) > 0 ? true : false;
    }

    public function getHash()
    {
        return $this->hash;
    }

    public function getPivotAttributes()
    {
        return $this->pivotAttributes;
    }

    public function __toString()
    {
        return $this->hash;
    }
}