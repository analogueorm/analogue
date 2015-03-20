<?php namespace Analogue\ORM\System;

use Analogue\ORM\Mappable;

interface ProxyInterface {

    /**
     * Convert a proxy into the underlying related Object
     *
     * @return Mappable|EntityCollection
     */
	public function load();

}