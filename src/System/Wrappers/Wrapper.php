<?php

namespace Analogue\ORM\System\Wrappers;

use Analogue\ORM\System\InternallyMappable;
use Analogue\ORM\System\Proxies\ProxyFactory;
use Analogue\ORM\System\Proxies\CollectionProxy;

/**
 * The Wrapper Class provides a single interface access several Entity types
 */
abstract class Wrapper implements InternallyMappable
{
    /**
     * Original Entity Object
     *
     * @var mixed
     */
    protected $entity;

    /**
     * Corresponding EntityMap
     *
     * @var \Analogue\ORM\EntityMap
     */
    protected $entityMap;

    /**
     * @var \Analogue\ORM\System\Proxirs\ProxyFactory
     */
    protected $proxyFactory;

    /**
     * Wrapper constructor.
     * @param $entity
     * @param $entityMap
     */
    public function __construct($entity, $entityMap)
    {
        $this->entity = $entity;
        $this->entityMap = $entityMap;
        $this->proxyFactory = new ProxyFactory;
    }

    /**
     * Return the wrapped entity class
     *
     * @return mixed
     */
    public function getEntityClass()
    {
        return get_class($this->entity);
    }

    /**
     * Returns the wrapped entity
     *
     * @return mixed
     */
    public function getObject()
    {
        return $this->entity;
    }

    /**
     * Returns the wrapped entity's map
     *
     * @return mixed
     */
    public function getMap()
    {
        return $this->entityMap;
    }

    /**
     * Set the lazyloading proxies on the wrapped entity objet
     * 
     * @param  array  $relations  list of relations to be lazy loaded
     * 
     * @return void
     */
    public function setProxies(array $relations = null)
    {
        $attributes = $this->getEntityAttributes();
        $proxies = [];

        if(is_null($relations)) {
            $relations = $this->getRelationsToProxy();
        }

        foreach ($relations as $relation) {
            $targetClass = $this->entityMap->getTargettedClass($relation);
            $proxies[$relation] = $this->proxyFactory->make($this->getObject(), $relation, $targetClass);
        }

        foreach ($proxies as $key => $value) {
            $this->setEntityAttribute($key, $value);
        }
    }

    /**  
     * Determine which relations we have to build proxy for
     * 
     * @return array
     */
    protected function getRelationsToProxy()
    {
        $proxies = [];
        $attributes = $this->getEntityAttributes();

        foreach ($this->entityMap->getRelationships() as $relation) {
            if (!array_key_exists($relation, $attributes) || is_null($attributes[$relation])) {
                $proxies[] = $relation;
            }
        }

        return $proxies;
    }

    /**
     * @param string $key
     * @param string $value
     * @return mixed
     */
    abstract public function setEntityAttribute($key, $value);

    /**
     * @param string $key
     * @return mixed
     */
    abstract public function getEntityAttribute($key);

    /**
     * @param array $attributes
     * @return mixed
     */
    abstract public function setEntityAttributes(array $attributes);

    /**
     * @return mixed
     */
    abstract public function getEntityAttributes();

    /**
     * @param string $key
     * @return mixed
     */
    abstract public function hasAttribute($key);
}
