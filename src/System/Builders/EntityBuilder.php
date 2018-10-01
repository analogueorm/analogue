<?php

namespace Analogue\ORM\System\Builders;

use Analogue\ORM\System\InternallyMappable;
use Analogue\ORM\System\Mapper;
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
     * @var bool
     */
    protected $useCache;

    /**
     * EntityBuilder constructor.
     *
     * @param Mapper $mapper
     * @param array  $eagerLoads
     * @param bool   $useCache
     */
    public function __construct(Mapper $mapper, array $eagerLoads, bool $useCache = false)
    {
        $this->mapper = $mapper;

        $this->entityMap = $mapper->getEntityMap();

        $this->eagerLoads = $eagerLoads;

        $this->factory = new Factory();

        $this->useCache = $useCache;
    }

    /**
     * Convert an array of attributes into an entity, or retrieve entity instance from cache.
     *
     * @param array $attributes
     *
     * @return mixed
     */
    public function build(array $attributes)
    {
        // If the object we are building is a value object,
        // we won't be using the instance cache.
        if (!$this->useCache || $this->entityMap->getKeyName() === null) {
            return $this->buildEntity($attributes);
        }

        $instanceCache = $this->mapper->getInstanceCache();

        $id = $this->getPrimaryKeyValue($attributes);

        return $instanceCache->has($id) ? $instanceCache->get($id) : $this->buildEntity($attributes);
    }

    /**
     * Actually build an entity.
     *
     * @param array $attributes
     *
     * @return mixed
     */
    protected function buildEntity(array $attributes)
    {
        $wrapper = $this->getWrapperInstance();

        // Hydrate any embedded Value Object
        //
        // TODO Move this to the result builder instead,
        // as we'll handle this the same way as they were
        // eager loaded relationships.
        $this->hydrateValueObjects($attributes);

        $wrapper->setEntityAttributes($attributes);

        $wrapper->setProxies();

        $entity = $wrapper->unwrap();

        // Once the object has been hydrated, we'll add
        // the instance to the instance cache.
        if ($this->entityMap->getKeyName() !== null) {
            $id = $this->getPrimaryKeyValue($attributes);
            $this->mapper->getInstanceCache()->add($entity, $id);
        }

        return $entity;
    }

    /**
     * Return the primary key value from attributes.
     *
     * @param array $attributes
     *
     * @return string
     */
    protected function getPrimaryKeyValue(array $attributes)
    {
        return $attributes[$this->entityMap->getKeyName()];
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
