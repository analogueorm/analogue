<?php namespace Analogue\ORM\System;

/**
 * This class Build a mappable object from a result set.
 */
class EntityBuilder {

    protected $mapper;

    protected $entityMap;

    protected $eagerLoads;

    public function __construct(Mapper $mapper, array $eagerLoads)
    {
        $this->mapper = $mapper;

        $this->eagerLoads = $eagerLoads;

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

            $proxies = $this->getLazyLoadingProxies($instance);

            if(count($proxies) > 0)
            {
                $instance->setEntityAttributes($resultArray + $proxies);
            }

            $entities[] = $instance;
        }

        $this->mapper->getEntityCache()->add($tmpCache);

        return $entities;
    }

    protected function hydrateValueObjects(& $attributes)
    {
        foreach($this->entityMap->getEmbeddables() as $localKey => $valueClass)
        {
            $this->hydrateValueObject($attributes, $localKey, $valueClass);
        }   
    }

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
     * Build an array with lazy loading proxies for the current query
     *
     * @param 
     * 
     * @return array           
     */
    protected function getLazyLoadingProxies($entity)
    {
        $entityMap = $this->entityMap;

        if(! $entityMap->relationsParsed() )
        {
            $initializer = new MapInitializer($entityMap);

            $initializer->splitRelationsTypes($entity);
        }

        $singleRelations = $entityMap->getSingleRelationships();
        $manyRelations = $entityMap->getManyRelationships();

        $eagerLoads = array_keys($this->eagerLoads);

        $allRelations = array_merge($manyRelations,$singleRelations);

        $lazyLoad = array_diff($allRelations, $eagerLoads);

        $proxies = [];

        if (count($lazyLoad) > 0)
        {
            foreach($lazyLoad as $relation)
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
        }

        return $proxies;
    }
}
