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
     * Get the entity class name.
     *
     * @return string
     */
    public function getEntityClass(): string;

    /**
     * Return the entity's primary key name.
     *
     * @return string
     */
    public function getEntityKeyName(): string;

    /**
     * Return the entity's primary key value.
     *
     * @return mixed
     */
    public function getEntityKeyValue();

    /**
     * Return the Entity's hash $class.$id.
     *
     * @return string
     */
    public function getEntityHash(): string;

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
     * @param mixed  $value
     */
    public function setEntityAttribute(string $key, $value);

    /**
     * Return the entity's attribute.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function getEntityAttribute(string $key);

    /**
     * Does the entity posses the given attribute.
     *
     * @param string $key
     *
     * @return bool
     */
    public function hasAttribute(string $key): bool;
}
