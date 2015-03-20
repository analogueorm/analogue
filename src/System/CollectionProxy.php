<?php namespace Analogue\ORM\System;

use Analogue\ORM\Mappable;
use Analogue\ORM\EntityCollection;

class CollectionProxy extends Proxy implements ProxyInterface {

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