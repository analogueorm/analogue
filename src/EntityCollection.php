<?php

namespace Analogue\ORM;

use Analogue\ORM\Exceptions\MappingException;
use Analogue\ORM\System\Manager;
use Analogue\ORM\System\Wrappers\Factory;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class EntityCollection extends Collection
{
    /**
     * Wrapper Factory.
     *
     * @var \Analogue\ORM\System\Wrappers\Factory
     */
    protected $factory;

    /**
     * EntityCollection constructor.
     *
     * @param array|null $entities
     */
    public function __construct(array $entities = null)
    {
        $this->factory = new Factory();

        parent::__construct($entities);
    }

    /**
     * Find an entity in the collection by key.
     *
     * @param mixed $key
     * @param mixed $default
     *
     * @throws MappingException
     *
     * @return \Analogue\ORM\Entity
     */
    public function find($key, $default = null)
    {
        if ($key instanceof Mappable) {
            $key = $this->getEntityKey($key);
        }

        return array_first($this->items, function ($entity, $itemKey) use ($key) {
            return $this->getEntityKey($entity) == $key;
        }, $default);
    }

    /**
     * Add an entity to the collection.
     *
     * @param Mappable $entity
     *
     * @return $this
     */
    public function add($entity)
    {
        $this->push($entity);

        return $this;
    }

    /**
     * Remove an entity from the collection.
     *
     * @param $entity
     *
     * @throws MappingException
     *
     * @return mixed
     */
    public function remove($entity)
    {
        $key = $this->getEntityKey($entity);

        return $this->pull($key);
    }

    /**
     * Get and remove an item from the collection.
     *
     * @param mixed $key
     * @param mixed $default
     *
     * @return mixed
     */
    public function pull($key, $default = null)
    {
        $this->items = array_filter($this->items, function ($item) use ($key) {
            $primaryKey = $this->getEntityKey($item);

            return $primaryKey !== $key;
        });
    }

    /**
     * Push an item onto the end of the collection.
     *
     * @param mixed $value
     *
     * @return void
     */
    public function push($value)
    {
        $this->offsetSet(null, $value);
    }

    /**
     * Put an item in the collection by key.
     *
     * @param mixed $key
     * @param mixed $value
     *
     * @return void
     */
    public function put($key, $value)
    {
        $this->offsetSet($key, $value);
    }

    /**
     * Set the item at a given offset.
     *
     * @param mixed $key
     * @param mixed $value
     *
     * @return void
     */
    public function offsetSet($key, $value)
    {
        if (is_null($key)) {
            $this->items[] = $value;
        } else {
            $this->items[$key] = $value;
        }
    }

    /**
     * Fetch a nested element of the collection.
     *
     * @param string $key
     *
     * @return self
     */
    public function fetch($key)
    {
        return new static(array_fetch($this->toArray(), $key));
    }

    /**
     * Generic function for returning class.key value pairs.
     *
     * @throws MappingException
     *
     * @return string
     */
    public function getEntityHashes()
    {
        return array_map(function ($entity) {
            $class = get_class($entity);

            $mapper = Manager::getMapper($class);

            $keyName = $mapper->getEntityMap()->getKeyName();

            return $class.'.'.$entity->getEntityAttribute($keyName);
        },
        $this->items);
    }

    /**
     * Get a subset of the collection from entity hashes.
     *
     * @param array $hashes
     *
     * @throws MappingException
     *
     * @return array
     */
    public function getSubsetByHashes(array $hashes)
    {
        $subset = [];

        foreach ($this->items as $item) {
            $class = get_class($item);

            $mapper = Manager::getMapper($class);

            $keyName = $mapper->getEntityMap()->getKeyName();

            if (in_array($class.'.'.$item->$keyName, $hashes)) {
                $subset[] = $item;
            }
        }

        return $subset;
    }

    /**
     * Merge the collection with the given items.
     *
     * @param array $items
     *
     * @throws MappingException
     *
     * @return self
     */
    public function merge($items)
    {
        $dictionary = $this->getDictionary();

        foreach ($items as $item) {
            $dictionary[$this->getEntityKey($item)] = $item;
        }

        return new static(array_values($dictionary));
    }

    /**
     * Diff the collection with the given items.
     *
     * @param \ArrayAccess|array $items
     *
     * @return self
     */
    public function diff($items)
    {
        $diff = new static();

        $dictionary = $this->getDictionary($items);

        foreach ($this->items as $item) {
            if (!isset($dictionary[$this->getEntityKey($item)])) {
                $diff->add($item);
            }
        }

        return $diff;
    }

    /**
     * Intersect the collection with the given items.
     *
     * @param \ArrayAccess|array $items
     *
     * @throws MappingException
     *
     * @return self
     */
    public function intersect($items)
    {
        $intersect = new static();

        $dictionary = $this->getDictionary($items);

        foreach ($this->items as $item) {
            if (isset($dictionary[$this->getEntityKey($item)])) {
                $intersect->add($item);
            }
        }

        return $intersect;
    }

    /**
     * Returns only the models from the collection with the specified keys.
     *
     * @param mixed $keys
     *
     * @return self
     */
    public function only($keys)
    {
        $dictionary = array_only($this->getDictionary(), $keys);

        return new static(array_values($dictionary));
    }

    /**
     * Returns all models in the collection except the models with specified keys.
     *
     * @param mixed $keys
     *
     * @return self
     */
    public function except($keys)
    {
        $dictionary = array_except($this->getDictionary(), $keys);

        return new static(array_values($dictionary));
    }

    /**
     * Get a dictionary keyed by primary keys.
     *
     * @param \ArrayAccess|array $items
     *
     * @throws MappingException
     *
     * @return array
     */
    public function getDictionary($items = null)
    {
        $items = is_null($items) ? $this->items : $items;

        $dictionary = [];

        foreach ($items as $value) {
            $dictionary[$this->getEntityKey($value)] = $value;
        }

        return $dictionary;
    }

    /**
     * @throws MappingException
     *
     * @return array
     */
    public function getEntityKeys()
    {
        return array_keys($this->getDictionary());
    }

    /**
     * @param $entity
     *
     * @throws MappingException
     *
     * @return mixed
     */
    protected function getEntityKey($entity)
    {
        $keyName = Manager::getMapper($entity)->getEntityMap()->getKeyName();

        $wrapper = $this->factory->make($entity);

        return $wrapper->getEntityAttribute($keyName);
    }

    /**
     * Get the max value of a given key.
     *
     * @param string|null $key
     *
     * @throws MappingException
     *
     * @return mixed
     */
    public function max($key = null)
    {
        return $this->reduce(function ($result, $item) use ($key) {
            $wrapper = $this->factory->make($item);

            return (is_null($result) || $wrapper->getEntityAttribute($key) > $result) ?
                $wrapper->getEntityAttribute($key) : $result;
        });
    }

    /**
     * Get the min value of a given key.
     *
     * @param string|null $key
     *
     * @throws MappingException
     *
     * @return mixed
     */
    public function min($key = null)
    {
        return $this->reduce(function ($result, $item) use ($key) {
            $wrapper = $this->factory->make($item);

            return (is_null($result) || $wrapper->getEntityAttribute($key) < $result)
                ? $wrapper->getEntityAttribute($key) : $result;
        });
    }

    /**
     * Get an array with the values of a given key.
     *
     * @param string      $value
     * @param string|null $key
     *
     * @return \Illuminate\Support\Collection
     */
    public function pluck($value, $key = null)
    {
        return new Collection(Arr::pluck($this->items, $value, $key));
    }

    /**
     * Alias for the "pluck" method.
     *
     * @param string      $value
     * @param string|null $key
     *
     * @return \Illuminate\Support\Collection
     */
    public function lists($value, $key = null)
    {
        return $this->pluck($value, $key);
    }

    /**
     * Unset the item at a given offset.
     *
     * @param string $key
     *
     * @return void
     */
    public function offsetUnset($key)
    {
        $this->items = array_filter($this->items, function ($item) use ($key) {
            $primaryKey = $this->getEntityKey($item);

            return $primaryKey !== $key;
        });
    }

    /**
     * Get a base Support collection instance from this collection.
     *
     * @return \Illuminate\Support\Collection
     */
    public function toBase()
    {
        return new Collection($this->items);
    }

    public function toArray()
    {
        return array_values(parent::toArray());
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
        $collection = new Collection(array_values($this->items));

        return $collection->toJson($options);
    }
}
