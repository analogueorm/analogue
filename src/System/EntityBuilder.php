<?php

namespace Analogue\ORM\System;

use Analogue\ORM\System\Wrappers\Factory;

/**
 * This class builds an array of Entity object(s) from a result set.
 */
class EntityBuilder
{
    /**
     * The mapper for the entity to build.
     *
     * @var \Analogue\ORM\System\Mapper
     */
    protected $mapper;

    /**
     * The Entity Map for the entity to build.
     *
     * @var \Analogue\ORM\EntityMap
     */
    protected $entityMap;

    /**
     * Relations that are eager loaded on this query.
     *
     * @var array
     */
    protected $eagerLoads;

    /**
     * @var array
     */
    protected $casts;

    /**
     * Entity Wrapper Factory.
     *
     * @var \Analogue\ORM\System\Wrappers\Factory
     */
    protected $factory;

    /**
     * EntityBuilder constructor.
     *
     * @param Mapper $mapper
     * @param array  $eagerLoads
     */
    public function __construct(Mapper $mapper, array $eagerLoads)
    {
        $this->mapper = $mapper;

        $this->entityMap = $mapper->getEntityMap();

        $this->eagerLoads = $eagerLoads;

        $this->factory = new Factory();
    }

    /**
     * Convert an array of values into an entity.
     *
     * @param array $result
     *
     * @return array
     */
    public function build(array $result)
    {
        $wrapper = $this->getWrapperInstance();

        // Hydrate any embedded Value Object
        //
        // TODO Move this to the result builder instead,
        // as we'll handle this the same way as they were
        // eager loaded relationships.
        $this->hydrateValueObjects($result);

        $wrapper->setEntityAttributes($result);

        $wrapper->setProxies();
        
        // Hydrate and return the instance
        $wrapper->hydrate();
        $entity = $wrapper->getObject();

        return $entity;
    }

    /**
     * Get the correct wrapper prototype corresponding to the object type.
     *
     * @throws \Analogue\ORM\Exceptions\MappingException
     *
     * @return InternallyMappable
     */
    protected function getWrapperInstance()
    {
        return $this->factory->make($this->mapper->newInstance());
    }

    /**
     * Hydrate value object embedded in this entity.
     *
     * @param array $attributes
     *
     * @throws \Analogue\ORM\Exceptions\MappingException
     *
     * @return void
     */
    protected function hydrateValueObjects(&$attributes)
    {
        foreach ($this->entityMap->getEmbeddables() as $localKey => $valueClass) {
            $this->hydrateValueObject($attributes, $localKey, $valueClass);
        }
    }

    /**
     * Hydrate a single value object.
     *
     * @param array  $attributes
     * @param string $localKey
     * @param string $valueClass
     *
     * @throws \Analogue\ORM\Exceptions\MappingException
     *
     * @return void
     */
    protected function hydrateValueObject(&$attributes, $localKey, $valueClass)
    {
        $map = $this->mapper->getManager()->getValueMap($valueClass);

        $embeddedAttributes = $map->getAttributes();

        $valueObject = $this->mapper->getManager()->getValueObjectInstance($valueClass);
        $voWrapper = $this->factory->make($valueObject);

        foreach ($embeddedAttributes as $key) {
            $prefix = snake_case(class_basename($valueClass)).'_';

            $voWrapper->setEntityAttribute($key, $attributes[$prefix.$key]);

            unset($attributes[$prefix.$key]);
        }

        $attributes[$localKey] = $voWrapper->getObject();
    }
}
