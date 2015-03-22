<?php namespace Analogue\ORM\System;

use ArrayAccess;
use Countable;
use JsonSerializable;
use IteratorAggregate;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Support\Arrayable;
use Analogue\ORM\Mappable;
use Analogue\ORM\EntityCollection;

class CollectionProxy extends Proxy implements ArrayAccess, Arrayable, Countable, IteratorAggregate, Jsonable, JsonSerializable{

	/**
	 * Underlying Lazyloaded collection
	 * @var EntityCollection
	 */
	protected $loadedCollection;

	/**
	 * Added Items Collection
	 * @var EntityCollection
	 */
	protected $addedItems;

	/**
	 * @param string $relation 	relationship method handled by the proxy.
	 */
	public function __construct(Mappable $parentEntity, $relation)
	{
		$this->addedItems = new EntityCollection;
		parent::__construct($parentEntity, $relation);
	}

	public function add(Mappable $entity)
	{
		if($this->isLoaded() )
		{
			return $this->loadedCollection->add($entity);
		}
		else
		{
			$this->addedItems->add($entity);
		}
	}

	public function isLoaded()
	{
		return ! is_null($this->loadedCollection);
	}

	public function getUnderlyingCollection()
	{
		return $this->loadedCollection;
	}

	public function getAddedItems()
	{
		return $this->addedItems;
	}

	protected function loadOnce()
	{
		$this->loadedCollection = $this->load();

		foreach($this->addedItems as $entity)
		{
			$this->loadedCollection->add($entity);
		}
	}

	/**
	 * Support Contracts
	 */
	
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
	 * @param  mixed  $key
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
	 * @param  mixed  $key
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
	 * @param  mixed  $key
	 * @param  mixed  $value
	 * @return void
	 */
	public function offsetSet($key, $value)
	{
		$this->loadOnce();

		return $this->getUnderlyingCollection()->offsetSet($key, $value);
	}

	/**
	 * Unset the item at a given offset.
	 *
	 * @param  string  $key
	 * @return void
	 */
	public function offsetUnset($key)
	{
		$this->loadOnce();

		return $this->getUnderlyingCollection()->offsetUnset($key);
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
	 * @param  int  $options
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
	 * Transparently Redirect non overrided calls to the lazy loaded collection
	 *  
	 * @param  [type] $method     [description]
	 * @param  [type] $parameters [description]
	 * @return [type]             [description]
	 */
	public function __call($method, $parameters)
	{
		if (! $this->isLoaded() ) $this->loadOnce();

		return call_user_func_array( [$this->loadedCollection, $method], $parameters);
	}
}