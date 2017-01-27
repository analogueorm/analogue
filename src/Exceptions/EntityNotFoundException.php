<?php

namespace Analogue\ORM\Exceptions;

use RuntimeException;

class EntityNotFoundException extends RuntimeException
{
    /**
     * Name of the affected Entity Map.
     *
     * @var string
     */
    protected $entity;

    /**
     * Set the affected Entity Map.
     *
     * @param string $entity
     *
     * @return $this
     */
    public function setEntity($entity)
    {
        $this->entity = $entity;

        $this->message = "No query results for entity [{$entity}].";

        return $this;
    }

    /**
     * Get the affected Entity.
     *
     * @return string
     */
    public function getEntity()
    {
        return $this->entity;
    }
}
