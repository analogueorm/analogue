<?php

namespace Analogue\ORM;

interface Mappable
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
}
