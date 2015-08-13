<?php
namespace Analogue\ORM;

use InvalidArgumentException;
use Analogue\ORM\Mappable;
use Analogue\ORM\System\Manager;
use Illuminate\Support\Collection as Collection;

class EntityCollection extends Collection {
	
	public function __construct(array $entities = null)
	{
		if ($entities) $this->checkItemsAreMappable($entities);

		parent::__construct($entities);
	}

	/**
	 * Check all the items implements Mappable
	 * 
	 * @param  array|ArrayAccess $entities 
	 * @return void
	 */
	protected function checkItemsAreMappable($entities)
	{
		foreach($entities as $entity)
		{
			$this->checkItemIsMappable($entity);
		}
	}

	protected function checkItemIsMappable($item)
	{
		if (! $item instanceof Mappable)
		{
			throw new InvalidArgumentException('Tried to assign non-mappable item to EntityCollection');
		}
	}

	/**
	 * Find an entity in the collection by key.
	 *
	 * @param  mixed  $key
	 * @param  mixed  $default
	 * 
	 * @return \Analogue\ORM\Entity
	 */
	public function find($key, $default = null)
	{
		if($key instanceof Mappable)
		{
			$key = $this->getEntityKey($key);
		}

		return array_first($this->items, function($itemKey, $entity) use ($key)
		{
			return $this->getEntityKey($entity) == $key;
		}, $default);
	}

	/**
	 * Add an entity to the collection.
	 *
	 * @param  Mappable  $entity
	 * @return $this
	 */
	public function add(Mappable $entity)
	{
		$this->push($entity);

		return $this;
	}

	/**
	 * Remove an entity from the collection
	 */
	public function remove($entity)
	{
		$keyName = $this->getEntityKey($entity);

		return $this->pull($entity->getEntityAttribute($keyName));
	}

	/**
	 * Push an item onto the beginning of the collection.
	 *
	 * @param  mixed  $value
	 * @return void
	 */
	public function prepend($value)
	{
		$this->checkItemIsMappable($value);

		array_unshift($this->items, $value);
	}

	/**
	 * Push an item onto the end of the collection.
	 *
	 * @param  mixed  $value
	 * @return void
	 */
	public function push($value)
	{
		$this->checkItemIsMappable($value);

		$this->offsetSet(null, $value);
	}

	/**
	 * Put an item in the collection by key.
	 *
	 * @param  mixed  $key
	 * @param  mixed  $value
	 * @return void
	 */
	public function put($key, $value)
	{
		$this->checkItemIsMappable($value);

		$this->offsetSet($key, $value);
	}

	/**
	 * Set the item at a given offset.
	 *
	 * @param  mixed  $key
	 * @param  mixed  $value
	 * @return void
	 */
	public function offsetSet($key, $value)
	{
		$this->checkItemIsMappable($value);
		
		if (is_null($key))
		{
			$this->items[] = $value;
		}
		else
		{
			$this->items[$key] = $value;
		}
	}

	/**
	 * Determine if a key exists in the collection.
	 *
	 * @param  mixed  $key
	 * @return bool
	 */
	public function contains($key, $value = null)
	{
		return ! is_null($this->find($key));
	}

	/**
	 * Fetch a nested element of the collection.
	 *
	 * @param  string  $key
	 * @return static
	 */
	public function fetch($key)
	{
		return new static(array_fetch($this->toArray(), $key));
	}

	/**
	 * Get the max value of a given key.
	 *
	 * @param  string  $key
	 * @return mixed
	 */
	public function max($key = null)
	{
		return $this->reduce(function($result, $item) use ($key)
		{
			return (is_null($result) || $item->getEntityAttribute($key) > $result) ? 
				$item->getEntityAttribute($key) : $result;
		});
	}

	/**
	 * Get the min value of a given key.
	 *
	 * @param  string  $key
	 * @return mixed
	 */
	public function min($key = null)
	{
		return $this->reduce(function($result, $item) use ($key)
		{
			return (is_null($result) || $item->getEntityAttribute($key) < $result) 
				? $item->getEntityAttribute($key) : $result;
		});
	}

	/**
	 * Generic function for returning class.key value pairs
	 * 
	 * @return string
	 */
	public function getEntityHashes()
	{
		return array_map(function($entity) 
		{ 
			$class = get_class($entity);

			$mapper = Manager::getMapper($class);
			
			$keyName = $mapper->getEntityMap()->getKeyName();
			
			return $class.'.'.$entity->getEntityAttribute($keyName); 
		}, 
		$this->items);
	}

	/**
	 * Get a subset of the collection from entity hashes
	 * 
	 * @param  array  $hashes 
	 * @return 
	 */
	public function getSubsetByHashes(array $hashes)
	{
		$subset = [];

		foreach($this->items as $item)
		{
			$class = get_class($item);

			$mapper = Manager::getMapper($class);
			
			$keyName = $mapper->getEntityMap()->getKeyName();

			if(in_array($class.'.'.$item->$keyName, $hashes)) $subset[] = $item; 
		}

		return $subset;
	}

	/**
	 * Merge the collection with the given items.
	 *
	 * @param  array  $items
	 * @return static
	 */
	public function merge($items)
	{
		$this->checkItemsAreMappable($items);

		$dictionary = $this->getDictionary();

		foreach ($items as $item)
		{
			$dictionary[$this->getEntityKey($item)] = $item;
		}

		return new static(array_values($dictionary));
	}

	/**
	 * Diff the collection with the given items.
	 *
	 * @param  \ArrayAccess|array  $items
	 * @return static
	 */
	public function diff($items)
	{
		$diff = new static;

		$dictionary = $this->getDictionary($items);

		foreach ($this->items as $item)
		{
			if ( ! isset($dictionary[$this->getEntityKey($item)]))
			{
				$diff->add($item);
			}
		}

		return $diff;
	}

	/**
	 * Intersect the collection with the given items.
	 *
 	 * @param  \ArrayAccess|array  $items
	 * @return static
	 */
	public function intersect($items)
	{
		$intersect = new static;

		$dictionary = $this->getDictionary($items);

		foreach ($this->items as $item)
		{
			if (isset($dictionary[$this->getEntityKey($item)]))
			{
				$intersect->add($item);
			}
		}

		return $intersect;
	}

	/**
	 * Return only unique items from the collection.
	 *
	 * @return static
	 */
	public function unique($key = null)
	{
		$dictionary = $this->getDictionary();

		return new static(array_values($dictionary));
	}

	/**
	 * Returns only the models from the collection with the specified keys.
	 *
	 * @param  mixed  $keys
	 * @return static
	 */
	public function only($keys)
	{
		$dictionary = array_only($this->getDictionary(), $keys);

		return new static(array_values($dictionary));
	}

	/**
	 * Returns all models in the collection except the models with specified keys.
	 *
	 * @param  mixed  $keys
	 * @return static
	 */
	public function except($keys)
	{
		$dictionary = array_except($this->getDictionary(), $keys);

		return new static(array_values($dictionary));
	}

	/**
	 * Get a dictionary keyed by primary keys.
	 *
	 * @param  \ArrayAccess|array  $items
	 * @return array
	 */
	public function getDictionary($items = null)
	{
		$items = is_null($items) ? $this->items : $items;

		$dictionary = array();

		foreach ($items as $value)
		{
			$dictionary[$this->getEntityKey($value)] = $value;
		}

		return $dictionary;
	}

	public function getEntityKeys()
	{
		return array_keys($this->getDictionary());
	}

	protected function getEntityKey(Mappable $entity)
	{
		$keyName = Manager::getMapper($entity)->getEntityMap()->getKeyName();
		
		return $entity->getEntityAttribute($keyName);
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
}
