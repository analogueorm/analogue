<?php

namespace Analogue\ORM\Events;

use Analogue\ORM\System\Mapper;

class Initialized
{
    /**
     * Mapper instance.
     *
     * @var Mapper
     */
    public $mapper;

    /**
     * Initialized constructor.
     *
     * @param Mapper $mapper
     */
    public function __construct(Mapper $mapper)
    {
        $this->mapper = $mapper;
    }
}
