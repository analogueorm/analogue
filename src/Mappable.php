<?php

namespace Analogue\ORM;

/**
 * @deprecated as 5.5 uses reflection based mapping
 */
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
