<?php namespace Analogue\ORM\System;

use Analogue\ORM\Mappable;

interface ProxyInterface {

    /**
     * Convert a proxy into the underlying related Object
     * @param  Mappable $entity   
     * @param  $relation
     * @return Mappable|EntityCollection
     */
	public function load(Mappable $entity);

}