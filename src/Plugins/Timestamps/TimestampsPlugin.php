<?php namespace Analogue\ORM\Plugins\Timestamps;

use Carbon\Carbon;
use Analogue\ORM\Entity;
use Analogue\ORM\EntityMap;
use Analogue\ORM\System\Manager;
use Analogue\ORM\Plugins\AnaloguePlugin;
use Analogue\ORM\System\Wrappers\Factory;

/**
 * Implements the Timestamps support on Analogue Entities
 */
class TimestampsPlugin extends AnaloguePlugin
{

    /**
     * Register the plugin
     *
     * @return void
     */
    public function register()
    {
        $this->manager->registerGlobalEvent('initialized', function ($mapper) {
            $entityMap = $mapper->getEntityMap();

            if ($entityMap->usesTimestamps()) {
                $mapper->registerEvent('creating', function ($entity) use ($entityMap) {
                    
                    $factory = new Factory;
                    $wrappedEntity = $factory->make($entity);

                    $createdAtField = $entityMap->getCreatedAtColumn();
                    $updatedAtField = $entityMap->getUpdatedAtColumn();

                    $time= new Carbon;

                    $wrappedEntity->setEntityAttribute($createdAtField, $time);
                    $wrappedEntity->setEntityAttribute($updatedAtField, $time);

                });

                $mapper->registerEvent('updating', function ($entity) use ($entityMap) {

                    $factory = new Factory;
                    $wrappedEntity = $factory->make($entity);

                    $updatedAtField = $entityMap->getUpdatedAtColumn();

                    $time= new Carbon;

                    $wrappedEntity->setEntityAttribute($updatedAtField, $time);
                });
            }
        });
    }

    /**
     * Get custom events provided by the plugin
     *
     * @return array
     */
    public function getCustomEvents()
    {
        return [];
    }
}
