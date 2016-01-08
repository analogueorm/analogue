<?php

namespace Analogue\ORM\System\Proxies;

use Analogue\ORM\System\Manager;

abstract class Proxy implements ProxyInterface
{
    /**
     * The name of the relationship method handled by the proxy.
     *
     * @var string
     */
    protected $relation;

    /**
     * Reference to parent entity object
     *
     * @var \Analogue\ORM\System\InternallyMappable
     */
    protected $parentEntity;

    /**
     * Lazy loaded relation flag
     *
     * @var boolean
     */
    protected $loaded = false;

    /**
     * @param mixed  $parentEntity
     * @param string $relation     relationship method handled by the proxy.
     */
    public function __construct($parentEntity, $relation)
    {
        $this->parentEntity = $parentEntity;

        $this->relation = $relation;
    }

    /**
     * Call the relationship method on the underlying entity map
     *
     * @return mixed
     */
    public function load()
    {
        $entities = $this->query($this->parentEntity, $this->relation)->getResults($this->relation);

        $this->loaded = true;

        return $entities;
    }

    /**
     * Return true if the underlying relation has been lazy loaded
     *
     * @return boolean
     */
    public function isLoaded()
    {
        return $this->loaded;
    }

    /**
     * Return the Query Builder on the relation
     *
     * @param  \Analogue\ORM\System\InternallyMappable  $entity
     * @param  string $relation
     * @return \Analogue\ORM\System\Query
     */
    protected function query($entity, $relation)
    {
        $entityMap = $this->getMapper($entity)->getEntityMap();

        return $entityMap->$relation($entity);
    }

    /**
     * Get the mapper instance for the entity
     *
     * @param  \Analogue\ORM\System\InternallyMappable $entity
     * @return \Analogue\ORM\System\Mapper
     */
    protected function getMapper($entity)
    {
        return Manager::getMapper($entity);
    }
}
