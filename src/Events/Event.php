<?php

namespace Analogue\ORM\Events;

abstract class Event
{
    /**
     * @var mixed
     */
    public $entity;

    /**
     * @param mixed $entity
     */
    public function __construct($entity)
    {
        $this->entity = $entity;
    }
}
