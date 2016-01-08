<?php

namespace Analogue\ORM;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Analogue\ORM\System\Manager
 */
class AnalogueFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'analogue';
    }
}
