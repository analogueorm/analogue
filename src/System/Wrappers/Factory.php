<?php namespace Analogue\ORM\System\Wrappers;

use Analogue\ORM\Mappable;
use Analogue\ORM\System\Manager;

class Factory {

    /**
     * Build the wrapper corresponding to the object's type
     * 
     * @param  mixed $object 
     * @return Wrapper
     */
    public function make($object)
    {
        if ($object instanceof Mappable) 
        {
            return new EntityWrapper($object);
        }
        else
        {
            // We have to retrive the attribute list from the entity map
            $entityMap = Manager::getMapper($object)->getEntityMap();

            return new PlainObjectWrapper($object, $entityMap);
        }
    }

}