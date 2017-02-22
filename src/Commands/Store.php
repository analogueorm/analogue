<?php

namespace Analogue\ORM\Commands;

use Analogue\ORM\EntityCollection;
use Analogue\ORM\Mappable;
use Analogue\ORM\System\Aggregate;
use Analogue\ORM\System\Proxies\CollectionProxy;
use Analogue\ORM\System\Proxies\EntityProxy;

/**
 * Persist entities & relationships to the
 * database.
 */
class Store extends Command
{
    /**
     * Persist the entity in the database.
     *
     * @throws \InvalidArgumentException
     *
     * @return false|mixed
     */
    public function execute()
    {
        $entity = $this->aggregate->getEntityObject();

        $mapper = $this->aggregate->getMapper();

        if ($mapper->fireEvent('storing', $entity) === false) {
            return false;
        }

        $this->preStoreProcess();

        /*
         * We will test the entity for existence
         * and run a creation if it doesn't exists
         */
        if (!$this->aggregate->exists()) {
            if ($mapper->fireEvent('creating', $entity) === false) {
                return false;
            }

            $this->insert();

            $mapper->fireEvent('created', $entity, false);
        } elseif ($this->aggregate->isDirty()) {
            if ($mapper->fireEvent('updating', $entity) === false) {
                return false;
            }
            $this->update();

            $mapper->fireEvent('updated', $entity, false);
        }

        $this->postStoreProcess();

        $mapper->fireEvent('stored', $entity, false);

        return $entity;
    }

    /**
     * Run all operations that have to occur before actually
     * storing the entity.
     *
     * @throws \InvalidArgumentException
     *
     * @return void
     */
    protected function preStoreProcess()
    {
        // Create any related object that doesn't exist in the database.
        $localRelationships = $this->aggregate->getEntityMap()->getLocalRelationships();

        $this->createRelatedEntities($localRelationships);

        // Now we can sync the related collections
        $this->aggregate->syncRelationships($localRelationships);
    }

    /**
     * Check for existence and create non-existing related entities.
     *
     * @param  array
     *
     * @throws \InvalidArgumentException
     *
     * @return void
     */
    protected function createRelatedEntities($relations)
    {
        $entitiesToCreate = $this->aggregate->getNonExistingRelated($relations);

        foreach ($entitiesToCreate as $aggregate) {
            $this->createStoreCommand($aggregate)->execute();
        }
    }

    /**
     * Create a new store command.
     *
     * @param Aggregate $aggregate
     *
     * @return Store
     */
    protected function createStoreCommand(Aggregate $aggregate)
    {
        // We gotta retrieve the corresponding query adapter to use.
        $mapper = $aggregate->getMapper();

        return new self($aggregate, $mapper->newQueryBuilder());
    }

    /**
     * Run all operations that have to occur after the entity
     * is stored.
     *
     * @throws \InvalidArgumentException
     *
     * @return void
     */
    protected function postStoreProcess()
    {
        $aggregate = $this->aggregate;

        // Create any related object that doesn't exist in the database.
        $foreignRelationships = $aggregate->getEntityMap()->getForeignRelationships();
        $this->createRelatedEntities($foreignRelationships);

        // Update any pivot tables that has been modified.
        $aggregate->updatePivotRecords();

        // Update any dirty relationship. This include relationships that already exists, have
        // dirty attributes / newly created related entities / dirty related entities.
        $dirtyRelatedAggregates = $aggregate->getDirtyRelationships();

        foreach ($dirtyRelatedAggregates as $related) {
            $this->createStoreCommand($related)->execute();
        }

        // Now we can sync the related collections
        if ($this->aggregate->exists()) {
            $this->aggregate->syncRelationships($foreignRelationships);
        }

        // TODO be move it to the wrapper class
        // so it's the same code for the entity builder
        $aggregate->setProxies();

        // Update Entity Cache
        $aggregate->getMapper()->getEntityCache()->refresh($aggregate);
    }

    /**
     * Update Related Entities which attributes have
     * been modified.
     *
     * @return void
     */
    protected function updateDirtyRelated()
    {
        $relations = $this->entityMap->getRelationships();
        $attributes = $this->getAttributes();

        foreach ($relations as $relation) {
            if (!array_key_exists($relation, $attributes)) {
                continue;
            }

            $value = $attributes[$relation];

            if ($value == null) {
                continue;
            }

            if ($value instanceof EntityProxy) {
                continue;
            }

            if ($value instanceof CollectionProxy && $value->isLoaded()) {
                $value = $value->getUnderlyingCollection();
            }
            if ($value instanceof CollectionProxy && !$value->isLoaded()) {
                foreach ($value->getAddedItems() as $entity) {
                    $this->updateEntityIfDirty($entity);
                }
                continue;
            }

            if ($value instanceof EntityCollection) {
                foreach ($value as $entity) {
                    if (!$this->createEntityIfNotExists($entity)) {
                        $this->updateEntityIfDirty($entity);
                    }
                }
                continue;
            }
            if ($value instanceof Mappable) {
                $this->updateEntityIfDirty($value);
                continue;
            }
        }
    }

    /**
     * Execute an insert statement on the database.
     *
     * @return void
     */
    protected function insert()
    {
        $aggregate = $this->aggregate;

        $attributes = $aggregate->getRawAttributes();

        $keyName = $aggregate->getEntityMap()->getKeyName();

        // Check if the primary key is defined in the attributes
        if (array_key_exists($keyName, $attributes) && $attributes[$keyName] != null) {
            $this->query->insert($attributes);
        } else {
            $sequence = $aggregate->getEntityMap()->getSequence();

            if (empty($attributes[$keyName])) {
                unset($attributes[$keyName]);
            }

            $id = $this->query->insertGetId($attributes, $sequence);

            $aggregate->setEntityAttribute($keyName, $id);
        }
    }

    /**
     * Run an update statement on the entity.
     *
     * @throws \InvalidArgumentException
     *
     * @return void
     */
    protected function update()
    {
        $query = $this->query;

        $keyName = $this->aggregate->getEntityKey();

        $query = $query->where($keyName, '=', $this->aggregate->getEntityId());

        $dirtyAttributes = $this->aggregate->getDirtyRawAttributes();

        if (count($dirtyAttributes) > 0) {
            $query->update($dirtyAttributes);
        }
    }
}
