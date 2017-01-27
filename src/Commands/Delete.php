<?php

namespace Analogue\ORM\Commands;

use Analogue\ORM\Exceptions\MappingException;

class Delete extends Command
{
    /**
     * Execute the Delete Statement.
     *
     * @throws MappingException
     * @throws \InvalidArgumentException
     *
     * @return false|void
     */
    public function execute()
    {
        $aggregate = $this->aggregate;

        $entity = $aggregate->getEntityObject();

        $mapper = $aggregate->getMapper();

        if ($mapper->fireEvent('deleting', $entity) === false) {
            return false;
        }

        $keyName = $aggregate->getEntityMap()->getKeyName();

        $id = $this->aggregate->getEntityId();

        if (is_null($id)) {
            throw new MappingException('Executed a delete command on an entity with "null" as primary key');
        }

        $this->query->where($keyName, '=', $id)->delete();

        $mapper->fireEvent('deleted', $entity, false);

        // Once the Entity is successfully deleted, we'll just set the primary key to null.
        $aggregate->setEntityAttribute($keyName, null);
    }
}
