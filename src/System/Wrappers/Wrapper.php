<?php

namespace Analogue\ORM\System\Wrappers;

use Analogue\ORM\System\InternallyMappable;
use Analogue\ORM\System\Proxies\CollectionProxy;
use Analogue\ORM\System\Proxies\EntityProxy;

/**
 * The Wrapper Class provides a single interface access several Entity types.
 */
abstract class Wrapper implements InternallyMappable
{
    /**
     * Original Entity Object.
     *
     * @var mixed
     */
    protected $entity;

    /**
     * Corresponding EntityMap.
     *
     * @var \Analogue\ORM\EntityMap
     */
    protected $entityMap;

    /**
     * Wrapper constructor.
     *
     * @param $entity
     * @param $entityMap
     */
    public function __construct($entity, $entityMap)
    {
        $this->entity = $entity;
        $this->entityMap = $entityMap;
    }

    /**
     * Return the wrapped entity class.
     *
     * @return mixed
     */
    public function getEntityClass()
    {
        return get_class($this->entity);
    }

    /**
     * Returns the wrapped entity.
     *
     * @return mixed
     */
    public function getObject()
    {
        return $this->entity;
    }

    /**
     * Returns the wrapped entity's map.
     *
     * @return mixed
     */
    public function getMap()
    {
        return $this->entityMap;
    }

    /**
     * Set the lazyloading proxies on the wrapped entity objet.
     *
     * @return void
     */
    public function setProxies()
    {
        $attributes = $this->getEntityAttributes();
        $singleRelations = $this->entityMap->getSingleRelationships();
        $manyRelations = $this->entityMap->getManyRelationships();

        $proxies = [];

        foreach ($this->entityMap->getRelationships() as $relation) {
            if (!array_key_exists($relation, $attributes) || is_null($attributes[$relation])) {
                if (in_array($relation, $singleRelations)) {
                    $proxies[$relation] = new EntityProxy($this->getObject(), $relation);
                }
                if (in_array($relation, $manyRelations)) {
                    $proxies[$relation] = new CollectionProxy($this->getObject(), $relation);
                }
            }
        }

        foreach ($proxies as $key => $value) {
            $this->setEntityAttribute($key, $value);
        }
    }

    /**
     * @param string $key
     * @param string $value
     *
     * @return mixed
     */
    abstract public function setEntityAttribute($key, $value);

    /**
     * @param string $key
     *
     * @return mixed
     */
    abstract public function getEntityAttribute($key);

    /**
     * @param array $attributes
     *
     * @return mixed
     */
    abstract public function setEntityAttributes(array $attributes);

    /**
     * @return mixed
     */
    abstract public function getEntityAttributes();

    /**
     * @param string $key
     *
     * @return mixed
     */
    abstract public function hasAttribute($key);
}
