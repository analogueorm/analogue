<?php

use Analogue\ORM\System\Manager;
use Analogue\ORM\System\Mapper;

if (!function_exists('analogue')) {

    /**
     * Return analogue's manager instance.
     *
     * @return \Analogue\ORM\System\Manager
     */
    function analogue()
    {
        return Manager::getInstance();
    }
}

if (!function_exists('mapper')) {

    /**
     * Create a mapper for a given entity (static alias).
     *
     * @param \Analogue\ORM\Mappable|string $entity
     * @param mixed                         $entityMap
     *
     * @return Mapper
     */
    function mapper($entity, $entityMap = null)
    {
        return Manager::getMapper($entity, $entityMap);
    }
}

if (!function_exists('is_asociative_array')) {

    /**
     * Checks if an array is an asociative array.
     *
     * @param array $array
     *
     * @return bool
     */
    function is_asociative_array(array $array)
    {
        return count(array_filter(array_keys($array), 'is_string')) > 0;
    }
}
