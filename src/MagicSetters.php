<?php

namespace Analogue\ORM;

/**
 * This trait assumes that the MagicGetters trait is used as well.
 */
trait MagicSetters
{
    /**
     * Dynamically set attributes on the entity.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return void
     */
    public function __set($key, $value)
    {
        $this->attributes[$key] = $value;
    }

    /**
     * Unset an attribute on the entity.
     *
     * @param string $key
     *
     * @return void
     */
    public function __unset($key)
    {
        unset($this->attributes[$key]);
    }
}
