<?php

namespace Analogue\ORM\Events;

use Analogue\ORM\System\Mapper;

class Initialized
{
    public $mapper;

    public function __construct(Mapper $mapper)
    {
        $this->mapper = $mapper;
    }
}
