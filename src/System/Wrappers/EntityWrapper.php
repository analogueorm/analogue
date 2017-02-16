<?php

namespace Analogue\ORM\System\Wrappers;

/**
 * Simple Wrapper for Mappable objects.
 */
class EntityWrapper extends Wrapper
{
    /**
     * Method used by the mapper to set the object
     * attribute raw values (hydration).
     *
     * @param array $attributes
     *
     * @return void
     */
    public function setEntityAttributes(array $attributes)
    {
        $this->entity->setEntityAttributes($attributes);
    }

    /**
     * Method used by the mapper to get the
     * raw object's values.
     *
     * @return array
     */
    public function getEntityAttributes()
    {
        return $this->entity->getEntityAttributes();
    }

    /**
     * Method used by the mapper to set raw
     * key-value pair.
     *
     * @param string $key
     * @param string $value
     *
     * @return void
     */
    public function setEntityAttribute($key, $value)
    {
        $attributes = $this->entity->getEntityAttributes();

        $attributes[$key] = $value;

        $this->entity->setEntityAttributes($attributes);
    }

    /**
     * Method used by the mapper to get single
     * key-value pair.
     *
     * @param string $key
     *
     * @return mixed|null
     */
    public function getEntityAttribute($key)
    {
        if ($this->hasAttribute($key)) {
            $attributes = $this->entity->getEntityAttributes();

            return $attributes[$key];
        } else {
            return;
        }
    }

    /**
     * Test if a given attribute exists.
     *
     * @param string $key
     *
     * @return bool
     */
    public function hasAttribute($key)
    {
        $attributes = $this->entity->getEntityAttributes();

        if (array_key_exists($key, $attributes)) {
            return true;
        } else {
            return false;
        }
    }
}
