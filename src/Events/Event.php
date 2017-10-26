<?php

namespace Analogue\ORM\Events;

abstract class Event
{
    /**
     * Entity.
     *
     * @var mixed
     */
    public $entity;

    /**
     * Event constructor.
     *
     * @param mixed $entity
     */
    public function __construct($entity)
    {
        $this->entity = $entity;
    }
}
