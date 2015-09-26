<?php

use Analogue\ORM\System\Manager;

/**
 * Create a mapper for a given entity (static alias)
 * 
 * @param \Analogue\ORM\Mappable|string $entity
 * @param mixed $entityMap 
 * @return Mapper
 */
function mapper($entity,$entityMap = null)
{
    return Manager::getMapper($entity, $entityMap);
}