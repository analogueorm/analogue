<?php

namespace Analogue\ORM\Commands;

use Analogue\ORM\System\Aggregate;

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
        $wrappedEntity = $this->aggregate->getWrappedEntity();
        $mapper = $this->aggregate->getMapper();

        if ($mapper->fireEvent('storing', $wrappedEntity) === false) {
            return false;
        }

        $this->preStoreProcess();

        /*
         * We will test the entity for existence
         * and run a creation if it doesn't exists
         */
        if (!$this->aggregate->exists()) {
            if ($mapper->fireEvent('creating', $wrappedEntity) === false) {
                return false;
            }

            $this->insert();

            $mapper->fireEvent('created', $wrappedEntity, false);
        } elseif ($this->aggregate->isDirty()) {
            if ($mapper->fireEvent('updating', $wrappedEntity) === false) {
                return false;
            }
            $this->update();

            $mapper->fireEvent('updated', $wrappedEntity, false);
        }

        $this->postStoreProcess();

        $mapper->fireEvent('stored', $wrappedEntity, false);

        // Once the object is stored, add it to the Instance cache
        $key = $this->aggregate->getEntityKeyValue();

        if (!$mapper->getInstanceCache()->has($key)) {
            $mapper->getInstanceCache()->add($entity, $key);
        }

        $this->syncForeignKeyAttributes();

        $wrappedEntity->unwrap();

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
    protected function createStoreCommand(Aggregate $aggregate): self
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
        //
        // TODO (note) : not sure this check is needed, as we can assume
        // the aggregate exists in the Post Store Process
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
            // Prevent inserting with a null ID
            if (array_key_exists($keyName, $attributes)) {
                unset($attributes[$keyName]);
            }

            if (isset($attributes['attributes'])) {
                unset($attributes['attributes']);
            }

            $id = $this->query->insertGetId($attributes, $keyName);

            $aggregate->setEntityAttribute($keyName, $id);
        }
    }

    /**
     * Update attributes on actual entity.
     *
     * @param array $attributes
     *
     * @return void
     */
    protected function syncForeignKeyAttributes()
    {
        $attributes = $this->aggregate->getForeignKeyAttributes();

        foreach ($attributes as $key => $value) {
            $this->aggregate->setEntityAttribute($key, $value);
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
        $key = $this->aggregate->getEntityKeyName();
        $value = $this->aggregate->getEntityKeyValue();

        $this->query->where($key, $value);

        $dirtyAttributes = $this->aggregate->getDirtyRawAttributes();

        if (count($dirtyAttributes) > 0) {
            $this->query->update($dirtyAttributes);
        }
    }
}
