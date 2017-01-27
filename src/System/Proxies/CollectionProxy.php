<?php

namespace Analogue\ORM\System\Proxies;

use Analogue\ORM\EntityCollection;
use ArrayAccess;
use Countable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use IteratorAggregate;
use JsonSerializable;

/**
 * Class CollectionProxy.
 *
 * @mixin EntityCollection
 */
class CollectionProxy extends Proxy implements ArrayAccess, Arrayable, Countable, IteratorAggregate, Jsonable, JsonSerializable
{
    /**
     * Underlying Lazyloaded collection.
     *
     * @var EntityCollection
     */
    protected $loadedCollection;

    /**
     * Added Items Collection.
     *
     * @var EntityCollection
     */
    protected $addedItems;

    /**
     * @param mixed  $parentEntity
     * @param string $relation     relationship method handled by the proxy.
     */
    public function __construct($parentEntity, $relation)
    {
        $this->addedItems = new EntityCollection();

        parent::__construct($parentEntity, $relation);
    }

    /**
     * Add an entity to the proxy collection, weither it's loaded or not.
     *
     * @param mixed $entity
     *
     * @return self|void
     */
    public function add($entity)
    {
        if ($this->isLoaded()) {
            return $this->loadedCollection->add($entity);
        } else {
            $this->addedItems->add($entity);
        }
    }

    /**
     * Check if Proxy collection has been lazy-loaded.
     *
     * @return bool
     */
    public function isLoaded()
    {
        return !is_null($this->loadedCollection);
    }

    /**
     * Return the underlying collection.
     *
     * @return EntityCollection
     */
    public function getUnderlyingCollection()
    {
        return $this->loadedCollection;
    }

    /**
     * Return Items that has been added prior to lazy-loading.
     *
     * @return EntityCollection
     */
    public function getAddedItems()
    {
        return $this->addedItems;
    }

    /**
     * Load the underlying relation.
     *
     * @return void
     */
    protected function loadOnce()
    {
        if ($this->isLoaded()) {
            return;
        }

        $this->loadedCollection = $this->load();

        foreach ($this->addedItems as $entity) {
            $this->loadedCollection->add($entity);
        }

        $this->addedItems = null;
    }

    /**
     * Count the number of items in the collection.
     *
     * @return int
     */
    public function count()
    {
        $this->loadOnce();

        return $this->getUnderlyingCollection()->count();
    }

    /**
     * Determine if an item exists at an offset.
     *
     * @param mixed $key
     *
     * @return bool
     */
    public function offsetExists($key)
    {
        $this->loadOnce();

        return $this->getUnderlyingCollection()->offsetExists($key);
    }

    /**
     * Get an item at a given offset.
     *
     * @param mixed $key
     *
     * @return mixed
     */
    public function offsetGet($key)
    {
        $this->loadOnce();

        return $this->getUnderlyingCollection()->offsetGet($key);
    }

    /**
     * Set the item at a given offset.
     *
     * @param mixed $key
     * @param mixed $value
     */
    public function offsetSet($key, $value)
    {
        $this->loadOnce();

        $this->getUnderlyingCollection()->offsetSet($key, $value);
    }

    /**
     * Unset the item at a given offset.
     *
     * @param string $key
     */
    public function offsetUnset($key)
    {
        $this->loadOnce();

        $this->getUnderlyingCollection()->offsetUnset($key);
    }

    /**
     * Get the collection of items as a plain array.
     *
     * @return array
     */
    public function toArray()
    {
        $this->loadOnce();

        return $this->getUnderlyingCollection()->toArray();
    }

    /**
     * Convert the object into something JSON serializable.
     *
     * @return array
     */
    public function jsonSerialize()
    {
        $this->loadOnce();

        return $this->getUnderlyingCollection()->jsonSerialize();
    }

    /**
     * Get the collection of items as JSON.
     *
     * @param int $options
     *
     * @return string
     */
    public function toJson($options = 0)
    {
        $this->loadOnce();

        return $this->getUnderlyingCollection()->toJson();
    }

    /**
     * Get an iterator for the items.
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        $this->loadOnce();

        return $this->getUnderlyingCollection()->getIterator();
    }

    /**
     * @param  $method
     * @param  $parameters
     *
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if (!$this->isLoaded()) {
            $this->loadOnce();
        }

        return call_user_func_array([$this->loadedCollection, $method], $parameters);
    }
}
