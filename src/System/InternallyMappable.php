<?php

namespace Analogue\ORM\System;

interface InternallyMappable
{
    /**
     * Set the object attribute raw values (hydration).
     *
     * @param array $attributes
     */
    public function setEntityAttributes(array $attributes);

    /**
     * Get the raw object's values.
     *
     * @return array
     */
    public function getEntityAttributes();

    /**
     * Set the raw entity attributes.
     *
     * @param string $key
     * @param string $value
     */
    public function setEntityAttribute($key, $value);

    /**
     * Return the entity's attribute.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function getEntityAttribute($key);

    /**
     * Does the entity posses the given attribute.
     *
     * @param string $key
     *
     * @return bool
     */
    public function hasAttribute($key);
}
