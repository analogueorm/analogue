<?php

namespace Analogue\ORM\System\Proxies;

use Analogue\ORM\Mappable;

interface ProxyInterface
{
    /**
     * Convert a proxy into the underlying related Object.
     *
     * @return Mappable|\Analogue\ORM\EntityCollection
     */
    public function load();

    /**
     * Return true if the underlying relation has been lazy loaded.
     *
     * @return bool
     */
    public function isLoaded();
}
