<?php

namespace Analogue\ORM\System\Wrappers;

use Analogue\ORM\Mappable;
use Analogue\ORM\System\Manager;

class Factory
{
    /**
     * Build the wrapper corresponding to the object's type.
     *
     * @param mixed $object
     *
     * @throws \Analogue\ORM\Exceptions\MappingException
     *
     * @return Wrapper
     */
    public function make($object)
    {
        $manager = Manager::getInstance();

        if ($manager->isValueObject($object)) {
            $entityMap = $manager->getValueMap($object);
        } else {
            $entityMap = $manager->mapper($object)->getEntityMap();
        }

        if ($object instanceof Mappable) {
            return new EntityWrapper($object, $entityMap);
        } else {
            return new PlainObjectWrapper($object, $entityMap);
        }
    }
}
