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
        $wrappedEntity = $aggregate->getWrappedEntity();
        $mapper = $aggregate->getMapper();

        if ($mapper->fireEvent('deleting', $wrappedEntity) === false) {
            return false;
        }

        $keyName = $aggregate->getEntityMap()->getKeyName();

        $id = $this->aggregate->getEntityKeyValue();

        if (is_null($id)) {
            throw new MappingException('Executed a delete command on an entity with "null" as primary key');
        }

        $this->query->where($keyName, '=', $id)->delete();

        $mapper->fireEvent('deleted', $wrappedEntity, false);
    }
}
