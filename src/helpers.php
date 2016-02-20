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

/**
 * Store a mappable object
 * 
 * @param \Analogue\ORM\Mappable|string $entity
 * @param mixed $entityMap 
 * @return \Analogue\ORM\Mappable
 */
function store($entity,$entityMap = null)
{
    return mapper($entity, $entityMap)->store($entity);
}

/**
 * Delete a mappable object
 * 
 * @param \Analogue\ORM\Mappable|string $entity
 * @param mixed $entityMap 
 * @return void
 */
function delete($entity,$entityMap = null)
{
    return mapper($entity, $entityMap)->delete($entity);
}