<?php namespace Analogue\ORM;

use Analogue\ORM\System\ProxyInterface;

/**
 * Share behaviour of Entities/ValueObjects and allow
 * implementing mapping for custom classes 
 */
trait MappableTrait {

    /**
     * The Entity's Attributes
     * @var array
     */
    protected $attributes = [];

    /**
     * Method used by the mapper to set the object 
     * attribute raw values (hydration)
     * 
     * @param array $attributes 
     *
     * @return void
     */
    public function setEntityAttributes(array $attributes)
    {
        $this->attributes = $attributes;
    }

    /**
     * Method used by the mapper to get the 
     * raw object's values.
     *
     * @return array
     */
    public function getEntityAttributes()
    {
        return $this->attributes;
    }

    /**
     * Method used by the mapper to set raw
     * key-value pair
     * 
     * @param string $key  
     * @param string $value
     *
     * @return void
     */
    public function setEntityAttribute($key, $value)
    {
        $this->attributes[$key] = $value;
    }

    /**
     * Method used by the mapper to get single
     * key-value pair
     * 
     * @param  string $key 
     * @return mixed|null
     */
    public function getEntityAttribute($key)
    {
        if(array_key_exists($key, $this->attributes))
        {
            return $this->attributes[$key];
        }
        else return null;
    }

    /**
     * Dynamically retrieve attributes on the entity.
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
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

    /**
     * Determine if an attribute exists on the entity.
     *
     * @param  string  $key
     * @return bool
     */
    public function __isset($key)
    {
        return array_key_exists($key, $this->attributes); 
    }

    /**
     * Unset an attribute on the entity.
     *
     * @param  string  $key
     * @return void
     */
    public function __unset($key)
    {
        unset($this->attributes[$key]);
    }

    
    /**
     * Determine if the given attribute exists.
     *
     * @param  mixed  $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->$offset);
    }

    /**
     * Get the value for a given offset.
     *
     * @param  mixed  $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->$offset;
    }

    /**
     * Set the value for a given offset.
     *
     * @param  mixed  $offset
     * @param  mixed  $value
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->$offset = $value;
    }

    /**
     * Unset the value for a given offset.
     *
     * @param  mixed  $offset
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->$offset);
    }
    
    /**
     * Convert the object into something JSON serializable.
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }
    
    /**
     * Convert the entity instance to JSON.
     *
     * @param  int  $options
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->toArray(), $options);
    }

    
    /**
     * Convert Mappable object to array;
     * 
     * @return array
     */
    public function toArray()
    {
        return $this->attributesToArray($this->attributes);
    }

     /**
     * Transform the Object to array/json, 
     *  
     * @return array
     */
    protected function attributesToArray(array $sourceAttributes)
    {
        $attributes = [];

        foreach($sourceAttributes as $key => $attribute)
        {
            // If the attribute is a proxy, and hasn't be loaded, we discard
            // it from the returned set.
            if ($attribute instanceof ProxyInterface && ! $attribute->isLoaded()) continue;

            if ($attribute instanceof Arrayable)
            {
                $attributes[$key] = $attribute->toArray();
            }
            else
            {
                $attributes[$key] = $attribute;
            }
            // Edit, this is maybe not a good idea after all,
            // as if we pass the data to an JS client
            // something like user.address.city
            // is far more elegant and easier to memorize
            // than user.address_city
            // 
            // 
            // In that case we may want
            // if ($attribute instanceof ValueObject)
            // {
            //     $valueObjectAttributes = $attribute->toArray();

            //     $prefix=snake_case(class_basename($attribute)).'_';

            //     foreach($valueObjectAttributes as  $voKey => $voAttribute);
            //     {
            //         $attributes[$prefix.$voKey] = $voAttribute;
            //     }
            //     continue;
            // }

            // if ($attribute instanceof Mappable || $attribute instanceof Collection 
            //     || $attribute instanceof CollectionProxy )
            // {
            //     $attributes[$key] = $attribute->toArray();
            // }
            // else 
            // {
            //     $attributes[$key] = $attribute;
            // }
            
        }
        return $attributes;
    }
}
