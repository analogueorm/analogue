<?php

namespace Analogue\ORM\System;

/**
 * This class contains a helper methods
 */
class Support
{
    /**
     * Return true if an object is an array or iterator
     *
     * @param  mixed $argument
     * @return boolean
     */
    public static function isTraversable($argument)
    {
        return $argument instanceof \Traversable || is_array($argument);
    }
}
