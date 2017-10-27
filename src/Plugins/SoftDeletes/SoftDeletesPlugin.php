<?php

namespace Analogue\ORM\Plugins\SoftDeletes;

use Analogue\ORM\Plugins\AnaloguePlugin;
use Analogue\ORM\System\Mapper;
use Analogue\ORM\System\Wrappers\Factory;
use Carbon\Carbon;

/**
 * This Plugin enables a softDeletes behaviour equivalent
 * to Eloquent ORM.
 */
class SoftDeletesPlugin extends AnaloguePlugin
{
    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $host = $this;

        // Hook any mapper init and check the mapping include soft deletes.
        $this->manager->registerGlobalEvent('initialized', function ($event, $payload = null) use ($host) {

            // Cross Compatible Event handling with 5.3
            // TODO : find a replacement event handler
            if (is_null($payload)) {
                $mapper = $event;
            } else {
                $mapper = $payload[0]->mapper;
            }

            $entityMap = $mapper->getEntityMap();

            if ($entityMap->usesSoftDeletes()) {
                $host->registerSoftDelete($mapper);
            }
        });
    }

    /**
     * By hooking to the mapper initialization event, we can extend it
     * with the softDelete capacity.
     *
     * @param \Analogue\ORM\System\Mapper $mapper
     *
     * @throws \Analogue\ORM\Exceptions\MappingException
     *
     * @return bool|void
     */
    protected function registerSoftDelete(Mapper $mapper)
    {
        $entityMap = $mapper->getEntityMap();

        // Add Scopes
        $mapper->addGlobalScope(new SoftDeletingScope());

        $host = $this;

        // Register 'deleting' events
        $mapper->registerEvent('deleting', function ($entity) use ($entityMap, $host) {

            // Convert Entity into an EntityWrapper
            $factory = new Factory();

            $wrappedEntity = $factory->make($entity);

            $deletedAtField = $entityMap->getQualifiedDeletedAtColumn();

            if (!is_null($wrappedEntity->getEntityAttribute($deletedAtField))) {
                return true;
            }

            $time = new Carbon();

            $wrappedEntity->setEntityAttribute($deletedAtField, $time);

            $plainObject = $wrappedEntity->getObject();
            $host->manager->mapper(get_class($plainObject))->store($plainObject);

            return false;
        });

        // Register RestoreCommand
        $mapper->addCustomCommand(Restore::class);
    }

    /**
     * {@inheritdoc}
     */
    public function getCustomEvents(): array
    {
        return [
            'restoring',
            'restored',
        ];
    }
}
