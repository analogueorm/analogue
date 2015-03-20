<?php namespace Analogue\ORM;

use Analogue\ORM\System\ProxyInterface;

trait MappableTrait {

    protected $attributes = [];

    /**
     * Return the entity's attribute 
     * @param  string $key 
     * @return mixed
     */
    public function getEntityAttribute($key)
    {
        return $this->attributes[$key];
    }

    /**
     * Set the object attribute raw values (hydration)
     * 
     * @param array $attributes 
     */
    public function setEntityAttributes(array $attributes)
    {
        $this->attributes = $attributes;
    }

    /**
     * Get the raw object's values.
     * 
     * @return array
     */
    public function getEntityAttributes()
    {
        return $this->attributes;
    }

    /**
     * Set the raw entity attributes
     * @param string $key  
     * @param string $value
     */
    public function setEntityAttribute($key, $value)
    {
        $this->attributes[$key] = $value;
    }

    /**
     * Dynamically retrieve attributes on the entity.
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        if (! array_key_exists($key, $this->attributes))
        {
            return null;
        }
        if ($this->attributes[$key] instanceof ProxyInterface)
        {
            $this->attributes[$key] = $this->attributes[$key]->load();
        }
        return $this->attributes[$key];
    }

    /**
     * Dynamically set attributes on the entity.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return void
     */
    public function __set($key, $value)
    {
        $this->attributes[$key] = $value;
    }
}
