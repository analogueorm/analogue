<?php namespace Analogue\ORM\System;

use Analogue\ORM\Mappable;

interface ProxyInterface {

    /**
     * Convert a proxy into the underlying related Object
     *
     * @return Mappable|EntityCollection
     */
	public function load();

    /**
     * Return true if the underlying relation has been lazy loaded
     * 
     * @return boolean
     */
    public function isLoaded();
    
}