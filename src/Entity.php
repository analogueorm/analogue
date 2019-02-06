<?php

namespace Analogue\ORM;

class Entity extends ValueObject
{
    /**
     * Entities Hidden Attributes, that will be discarded when converting
     * the entity to Array/Json
     * (can include any embedded object's attribute).
     *
     * @var array
     */
    protected $hidden = [];

    /**
     * Return the entity's attribute.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function __get($key)
    {
        if ($this->hasGetMutator($key)) {
            $method = 'get'.$this->getMutatorMethod($key);

            $attribute = null;

            if (isset($this->attributes[$key])) {
                $attribute = $this->attributes[$key];
            }

            return $this->$method($attribute);
        }
        if (!array_key_exists($key, $this->attributes)) {
            return;
        }

        return $this->attributes[$key];
    }

    /**
     * Dynamically set attributes on the entity.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return void
     */
    public function __set($key, $value)
    {
        if ($this->hasSetMutator($key)) {
            $method = 'set'.$this->getMutatorMethod($key);

            $this->$method($value);
        } else {
            $this->attributes[$key] = $value;
        }
    }

    /**
     * Is a getter method defined ?
     *
     * @param string $key
     *
     * @return bool
     */
    protected function hasGetMutator($key)
    {
        return method_exists($this, 'get'.$this->getMutatorMethod($key));
    }

    /**
     * Is a setter method defined ?
     *
     * @param string $key
     *
     * @return bool
     */
    protected function hasSetMutator($key)
    {
        return method_exists($this, 'set'.$this->getMutatorMethod($key));
    }

    /**
     * @param $key
     *
     * @return string
     */
    protected function getMutatorMethod($key)
    {
        $key = ucwords(str_replace(['-', '_'], ' ', $key));

        return str_replace(' ', '', $key).'Attribute';
    }

    /**
     * Convert every attributes to value / arrays.
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
                $method = 'get'.$this->getMutatorMethod($key);
                $attributes[$key] = $this->$method($attribute);
            }
        }

        return $attributes;
    }

    /**
     * Fill an entity with key-value pairs.
     *
     * @param array $attributes
     *
     * @return void
     */
    public function fill(array $attributes)
    {
        foreach ($attributes as $key => $attribute) {
            $this->{$key} = $attribute;
        }
    }
}
