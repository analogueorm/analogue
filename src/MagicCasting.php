<?php

namespace Analogue\ORM;

use Carbon\Carbon;
use Illuminate\Contracts\Support\Arrayable;
use ProxyManager\Proxy\ProxyInterface;

trait MagicCasting
{
    /**
     * Determine if the given attribute exists.
     *
     * @param mixed $offset
     *
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->$offset);
    }

    /**
     * Get the value for a given offset.
     *
     * @param mixed $offset
     *
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->$offset;
    }

    /**
     * Set the value for a given offset.
     *
     * @param mixed $offset
     * @param mixed $value
     *
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->$offset = $value;
    }

    /**
     * Unset the value for a given offset.
     *
     * @param mixed $offset
     *
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
     * @param int $options
     *
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->toArray(), $options);
    }

    /**
     * Convert Mappable object to array;.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->attributesToArray($this->attributes);
    }

    /**
     * Transform the Object to array/json,.
     *
     * @param array $sourceAttributes
     *
     * @return array
     */
    protected function attributesToArray(array $sourceAttributes)
    {
        $attributes = [];

        foreach ($sourceAttributes as $key => $attribute) {
            // If the attribute is a proxy, and hasn't be loaded, we discard
            // it from the returned set.
            if ($attribute instanceof ProxyInterface && !$attribute->isProxyInitialized()) {
                continue;
            }

            if ($attribute instanceof Carbon) {
                $attributes[$key] = $attribute->__toString();
                continue;
            }

            if ($attribute instanceof Arrayable) {
                $attributes[$key] = $attribute->toArray();
            } else {
                $attributes[$key] = $attribute;
            }
        }

        return $attributes;
    }
}
