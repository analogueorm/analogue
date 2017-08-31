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
