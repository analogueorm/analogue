<?php namespace Analogue\ORM\System\Wrappers;

use Analogue\ORM\System\InternallyMappable;

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

    public function getObject()
    {
        return $this->entity;
    }

    abstract function setEntityAttribute($key, $value);

    abstract function getEntityAttribute($key);    

    abstract function setEntityAttributes(array $attributes);

    abstract function getEntityAttributes();
}