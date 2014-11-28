<?php namespace Analogue\ORM\System;

class CollectionProxy extends Proxy implements ProxyInterface {

	/**
	 * Transparently Redirect non overrided calls to the lazy loaded collection
	 * 
	 * * To implement for partial lazy loading *
	 * @param  [type] $method     [description]
	 * @param  [type] $parameters [description]
	 * @return [type]             [description]
	 */
	/*public function __call($method, $parameters)
	{

	}*/
}