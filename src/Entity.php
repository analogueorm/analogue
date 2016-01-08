<?php

namespace Analogue\ORM;

use Analogue\ORM\System\Proxies\EntityProxy;

class Entity extends ValueObject
{
    /**
     * Entities Hidden Attributes, that will be discarded when converting
     * the entity to Array/Json
     * (can include any embedded object's attribute)
     *
     * @var array
     */
    protected $hidden = [];

    /**
     * Return the entity's attribute
     * @param  string $key
     * @return mixed
     */
    public function __get($key)
    {
        if ($this->hasGetMutator($key)) {
            $method = 'get' . $this->getMutatorMethod($key);

            $attribute = null;

            if (isset($this->attributes[$key])) {
                $attribute = $this->attributes[$key];
            }

            return $this->$method($attribute);
        }
        if (!array_key_exists($key, $this->attributes)) {
            return null;
        }
        if ($this->attributes[$key] instanceof EntityProxy) {
            $this->attributes[$key] = $this->attributes[$key]->load();
        }
        return $this->attributes[$key];
    }

    /**
     * Dynamically set attributes on the entity.
     *
     * @param  string $key
     * @param  mixed  $value
     * @return void
     */
    public function __set($key, $value)
    {
        if ($this->hasSetMutator($key)) {
            $method = 'set' . $this->getMutatorMethod($key);

            $this->$method($value);
        } else {
            $this->attributes[$key] = $value;
        }
    }

    /**
     * Is a getter method defined ?
     *
     * @param  string $key
     * @return boolean
     */
    protected function hasGetMutator($key)
    {
        return method_exists($this, 'get' . $this->getMutatorMethod($key)) ? true : false;
    }

    /**
     * Is a setter method defined ?
     *
     * @param  string $key
     * @return boolean
     */
    protected function hasSetMutator($key)
    {
        return method_exists($this, 'set' . $this->getMutatorMethod($key)) ? true : false;
    }

    /**
     * @param $key
     * @return string
     */
    protected function getMutatorMethod($key)
    {
        return ucfirst($key) . 'Attribute';
    }

    /**
     * Convert every attributes to value / arrays
     *
     * @return array
     */
    public function toArray()
    {
        // First, call the trait method before filtering
        // with Entity specific methods
        $attributes = $this->attributesToArray($this->attributes);
        
        foreach ($this->attributes as $key => $attribute) {
            if (in_array($key, $this->hidden)) {
                unset($attributes[$key]);
                continue;
            }
            if ($this->hasGetMutator($key)) {
                $method = 'get' . $this->getMutatorMethod($key);
                $attributes[$key] = $this->$method($attribute);
            }
        }
        return $attributes;
    }
}
