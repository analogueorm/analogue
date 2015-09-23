<?php 

namespace Analogue\ORM\System\Wrappers;

use Analogue\ORM\EntityMap;
use Analogue\ORM\System\InternallyMappable;
use Analogue\ORM\System\Proxies\EntityProxy;
use Analogue\ORM\System\Proxies\CollectionProxy;

/**
 * The Wrapper Class intend to access several Entity types
 * with the same interface.
 * 
 */
abstract Class Wrapper implements InternallyMappable {
  
    protected $entity;

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
     * Set the lazyloading proxies on the wrapped entity objet
     *
     * @return void
     */
    public function setProxies()
    {
        $attributes = $this->getEntityAttributes();
        $singleRelations = $this->entityMap->getSingleRelationships();
        $manyRelations = $this->entityMap->getManyRelationships();

        $proxies = [];

        foreach($this->entityMap->getRelationships() as $relation)
        {
            if(! array_key_exists($relation, $attributes) || is_null($attributes[$relation]))
            {
                if (in_array($relation, $singleRelations))
                {
                    $proxies[$relation] = new EntityProxy($this->getObject(), $relation);
                }
                if (in_array($relation, $manyRelations))
                {   
                    $proxies[$relation] = new CollectionProxy($this->getObject(), $relation);
                }
            }
        }

        foreach($proxies as $key => $value)
        {   
            $this->setEntityAttribute($key, $value);
        }
    }

    abstract function setEntityAttribute($key, $value);

    abstract function getEntityAttribute($key);    

    abstract function setEntityAttributes(array $attributes);

    abstract function getEntityAttributes();

    abstract function hasAttribute($key);
}
