<?php namespace Analogue\ORM\System;

use Analogue\ORM\Mappable;

/**
 * This class Build a mappable object from a result set.
 */
class EntityBuilder {

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

    public function __construct(Mapper $mapper, array $eagerLoads)
    {
        $this->mapper = $mapper;

        $this->entityMap = $mapper->getEntityMap();

        $this->eagerLoads = $eagerLoads;

        $this->lazyLoads = $this->prepareLazyLoading();

        $this->entityMap = $mapper->getEntityMap();
    }

    /**
     * Build the Entity(ies)
     * 
     * @param  array  $results 
     * @return Mappable|EntityCollection
     */
    public function build(array $results)
    {
        $entities = array();

        $prototype = $this->mapper->newInstance();

        $keyName = $this->entityMap->getKeyName();

        $tmpCache = [];

        foreach($results as $result)
        {
            $instance = clone $prototype;

            $resultArray = (array) $result;

            $tmpCache[$resultArray[$keyName] ] = $resultArray;

            // Hydrate any embedded Value Object
            $this->hydrateValueObjects($resultArray);

            $instance->setEntityAttributes($resultArray);

            // Hydrate relation attributes with lazyloading proxies
            if(count($this->lazyLoads) > 0)
            {
                $proxies = $this->getLazyLoadingProxies($instance);
                $instance->setEntityAttributes($resultArray + $proxies);
            }

            $entities[] = $instance;
        }

        $this->mapper->getEntityCache()->add($tmpCache);

        return $entities;
    }

    /**
     * Hydrate value object embedded in this entity
     * 
     * @param  array $attributes 
     * @return void
     */
    protected function hydrateValueObjects(& $attributes)
    {
        foreach($this->entityMap->getEmbeddables() as $localKey => $valueClass)
        {
            $this->hydrateValueObject($attributes, $localKey, $valueClass);
        }   
    }

    /**
     * Hydrate a single value object
     * 
     * @param  array $attributes 
     * @param  string $localKey   
     * @param  string $valueClass 
     * @return void
     */
    protected function hydrateValueObject(& $attributes, $localKey, $valueClass)
    {
        $map = $this->mapper->getManager()->getValueMap($valueClass);

        $embeddedAttributes = $map->getAttributes();

        //$nestedValueObjects = $map->getEmbeddables();

        $valueObject = $this->mapper->getManager()->getValueObjectInstance($valueClass);

        foreach($embeddedAttributes as $key)
        {
            $prefix = snake_case(class_basename($valueClass)).'_';

            $valueObject->setEntityAttribute($key, $attributes[$prefix.$key]);
            
            unset($attributes[$prefix.$key]);
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
        $entityMap = $this->entityMap;

        if(! $entityMap->relationsParsed() )
        {
            $initializer = new MapInitializer($entityMap);
            $initializer->splitRelationsTypes($this->mapper->newInstance());
        }

        $singleRelations = $entityMap->getSingleRelationships();
        $manyRelations = $entityMap->getManyRelationships();

        $allRelations = array_merge($manyRelations,$singleRelations);
       
        return array_diff($allRelations, $this->eagerLoads);
    }

    /**
     * Build lazy loading proxies for the current entity
     *
     * @param \Analogue\ORM\Mappable $entity
     * 
     * @return array           
     */
    protected function getLazyLoadingProxies(Mappable $entity)
    {
        $proxies = [];

        $singleRelations = $this->entityMap->getSingleRelationships();
        $manyRelations = $this->entityMap->getManyRelationships();

        foreach($this->lazyLoads as $relation)
        {
            if (in_array($relation, $singleRelations))
            {
                $proxies[$relation] = new EntityProxy($entity, $relation);
            }
            if (in_array($relation, $manyRelations))
            {   
                $proxies[$relation] = new CollectionProxy($entity, $relation);
            }
        }
        
        return $proxies;
    }
}
