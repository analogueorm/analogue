<?php

namespace Analogue\ORM\System\Wrappers;

use Analogue\ORM\System\Manager;
use GeneratedHydrator\Configuration;

class Factory
{
    /**
     * Build the wrapper corresponding to the object's type.
     *
     * @param mixed $object
     *
     * @throws \Analogue\ORM\Exceptions\MappingException
     *
     * @return ObjectWrapper
     */
    public function make($object)
    {
        $manager = Manager::getInstance();

        // Instantiate hydrator
        // TODO: We'll need to optimize this and allow pre-generation
        //       of these hydrators, and get it, ideally, from the entityMap or
        //       the Mapper class, so it's only instantiated once
        $config = new Configuration(get_class($object));

        $hydratorClass = $config->createFactory()->getHydratorClass();
        $hydrator = new $hydratorClass();

        if ($manager->isValueObject($object)) {
            $entityMap = $manager->getValueMap($object);
        } else {
            $entityMap = $manager->mapper($object)->getEntityMap();
        }

        // Build Wrapper
        return new ObjectWrapper($object, $entityMap, $hydrator);
    }
}
