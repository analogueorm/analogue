<?php

namespace Analogue\ORM\Plugins\SoftDeletes;

use Analogue\ORM\Commands\Command;

class Restore extends Command
{
    /**
     * @throws \InvalidArgumentException
     *
     * @return false|mixed
     */
    public function execute()
    {
        $aggregate = $this->aggregate;
        $entity = $aggregate->getEntityObject();
        $mapper = $aggregate->getMapper();
        $entityMap = $mapper->getEntityMap();

        if ($mapper->fireEvent('restoring', $entity) === false) {
            return false;
        }

        $keyName = $entityMap->getKeyName();

        $query = $this->query->where($keyName, '=', $aggregate->getEntityAttribute($keyName));

        $deletedAtColumn = $entityMap->getQualifiedDeletedAtColumn();

        $query->update([$deletedAtColumn => null]);

        $aggregate->setEntityAttribute($deletedAtColumn, null);

        $mapper->fireEvent('restored', $entity, false);

        $mapper->getEntityCache()->refresh($aggregate);

        return $entity;
    }
}
