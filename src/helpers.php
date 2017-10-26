<?php

use Analogue\ORM\System\Manager;

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
     * @return \Analogue\ORM\System\Mapper
     */
    function mapper($entity, $entityMap = null)
    {
        return Manager::getMapper($entity, $entityMap);
    }
}
