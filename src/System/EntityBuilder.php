<?php

namespace Analogue\ORM\System;

use Analogue\ORM\System\Wrappers\Factory;
use Analogue\ORM\System\Proxies\EntityProxy;
use Analogue\ORM\System\Proxies\CollectionProxy;

/**
 * This class builds an array of Entity object(s) from a result set.
 */
class EntityBuilder
{
    /**
     * The mapper for the entity to build
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
     * Relations that will be eager loaded on this query
     *
     * @var array
     */
    protected $eagerLoads;

    /**
     * Relations that will be lazy loaded on this query
     *
     * @var array
     */
    protected $lazyLoads;

    /**
     * Entity Wrapper Factory
     * @var \Analogue\ORM\System\Wrappers\Factory
     */
    protected $factory;

    /**
     * EntityBuilder constructor.
     * @param Mapper $mapper
     * @param array  $eagerLoads
     */
    public function __construct(Mapper $mapper, array $eagerLoads)
    {
        $this->mapper = $mapper;

        $this->entityMap = $mapper->getEntityMap();

        $this->eagerLoads = $eagerLoads;

        $this->lazyLoads = $this->prepareLazyLoading();

        $this->entityMap = $mapper->getEntityMap();

        $this->factory = new Factory;
    }

    /**
     * Convert a result set into an array of entities
     *
     * @param  array $results
     * @return array
     */
    public function build($results)
    {
        $entities = [];

        //$prototype = $this->getWrapperPrototype();
        //$prototype = $this->mapper->newInstance();

        $keyName = $this->entityMap->getKeyName();

        $tmpCache = [];

        foreach ($results as $result) {
            //$instance = clone $prototype;
            $instance = $this->getWrapperInstance();

            $resultArray = (array) $result;

            $tmpCache[$resultArray[$keyName]] = $resultArray;

            // Hydrate any embedded Value Object
            $this->hydrateValueObjects($resultArray);

            $resultArray = $this->entityMap->getAttributeNamesFromColumns($resultArray);

            $instance->setEntityAttributes($resultArray);

            // Hydrate relation attributes with lazyloading proxies
            if (count($this->lazyLoads) > 0) {
                $proxies = $this->getLazyLoadingProxies($instance);
                $instance->setEntityAttributes($resultArray + $proxies);
            }

            // Directly Unwrap the entity now that it has been hydrated
            $entities[] = $instance->getObject();
        }

        $this->mapper->getEntityCache()->add($tmpCache);

        return $entities;
    }

    /**
     * Get the correct wrapper prototype corresponding to the object type
     *
     * @throws \Analogue\ORM\Exceptions\MappingException
     * @return InternallyMappable
     */
    protected function getWrapperInstance()
    {
        return $this->factory->make($this->mapper->newInstance());
    }

    /**
     * Hydrate value object embedded in this entity
     *
     * @param  array $attributes
     * @throws \Analogue\ORM\Exceptions\MappingException
     * @return void
     */
    protected function hydrateValueObjects(& $attributes)
    {
        foreach ($this->entityMap->getEmbeddables() as $localKey => $valueClass) {
            $this->hydrateValueObject($attributes, $localKey, $valueClass);
        }
    }

    /**
     * Hydrate a single value object
     *
     * @param  array  $attributes
     * @param  string $localKey
     * @param  string $valueClass
     * @throws \Analogue\ORM\Exceptions\MappingException
     * @return void
     */
    protected function hydrateValueObject(& $attributes, $localKey, $valueClass)
    {
        $map = $this->mapper->getManager()->getValueMap($valueClass);

        $embeddedAttributes = $map->getAttributes();

        $valueObject = $this->mapper->getManager()->getValueObjectInstance($valueClass);

        foreach ($embeddedAttributes as $key) {
            $prefix = snake_case(class_basename($valueClass)) . '_';

            $voWrapper = $this->factory->make($valueObject);

            $voWrapper->setEntityAttribute($key, $attributes[$prefix . $key]);
            
            unset($attributes[$prefix . $key]);
        }
        
        $attributes[$localKey] = $valueObject;
    }

    /**
     * Deduce the relationships that will be lazy loaded from the eagerLoads array
     *
     * @return array
     */
    protected function prepareLazyLoading()
    {
        $relations = $this->entityMap->getRelationships();
       
        return array_diff($relations, $this->eagerLoads);
    }

    /**
     * Build lazy loading proxies for the current entity
     *
     * @param InternallyMappable $entity
     *
     * @return array
     */
    protected function getLazyLoadingProxies(InternallyMappable $entity)
    {
        $proxies = [];

        $singleRelations = $this->entityMap->getSingleRelationships();
        $manyRelations = $this->entityMap->getManyRelationships();

        foreach ($this->lazyLoads as $relation) {
            if (in_array($relation, $singleRelations)) {
                $proxies[$relation] = new EntityProxy($entity->getObject(), $relation);
            }
            if (in_array($relation, $manyRelations)) {
                $proxies[$relation] = new CollectionProxy($entity->getObject(), $relation);
            }
        }
        
        return $proxies;
    }
}
