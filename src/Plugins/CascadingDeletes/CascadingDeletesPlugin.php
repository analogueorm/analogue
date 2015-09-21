<?php namespace Analogue\ORM\Plugins\CascadingDeletes;

use Analogue\ORM\Entity;
use Analogue\ORM\EntityMap;
use Analogue\ORM\System\Manager;
use Analogue\ORM\Plugins\AnaloguePlugin;

use Analogue\ORM\Relationships\BelongsTo;
use Analogue\ORM\Relationships\BelongsToMany;
use Analogue\ORM\Relationships\HasOneOrMany;

class CascadingDeletesPlugin extends AnaloguePlugin {

    /**
     * Register the plugin
     * 
     * @return void
     */
    public function register()
    {
        $this->manager->registerGlobalEvent('initialized', function ($mapper)
        {
            $entityMap = $mapper->getEntityMap();

            $mapper->registerEvent('deleting', function(Entity $entity) use ($entityMap) {
                
                $relationsToCascade = $entityMap->getCascadeDeletesOn();
                
                foreach($relationsToCascade as $relation) {

                    if ($relation instanceof BelongsTo) {
                        // TODO: check if this was the last entity left, if so, delete related entity
                    }

                    if ($relation instanceof BelongsToMany) {
                        // TODO: check if this was the last entity left, if so, delete all related entities
                    }

                    if ($relation instanceof HasOneOrMany) {
                        // TODO: delete all related entities
                    }

                    if ($relation instanceof MorphOneOrMany) {
                        // TODO: check if this was the last entity left, if so, delete related entities
                    }

                }

            });

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
