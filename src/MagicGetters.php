<?php

namespace Analogue\ORM;

trait MagicGetters
{
    /**
     * Contains the entity's attributes.
     *
     * @var array
     */
    protected $attributes = [];

    /**
     * Dynamically retrieve attributes on the entity.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function __get($key)
    {
        // When using mixed mapping, we will check
        // for a class property corresponding to
        // the attribute's key first.
        //
        // Note : this may raise issues as we may grant
        // access to unwanted properties, like class dependencies.
        //
        // -> Solution would be to access the entityMap's $attributes, but we
        // have to do this in a very efficient way.
        //
        // Manager::getEntityMap(get_class($this))->hasProperty()
        //
        // We could do the casting to array / json the same way, and it would

        if (property_exists($this, $key)) {
            return $this->$key;
        }

        if (array_key_exists($key, $this->attributes)) {
            return $this->attributes[$key];
        }
    }

    /**
     * Determine if an attribute exists on the entity.
     *
     * @param string $key
     *
     * @return bool
     */
    public function __isset($key)
    {
        return array_key_exists($key, $this->attributes) || property_exists($this, $key);
    }
}
