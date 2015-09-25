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

            $mapper->registerEvent('deleting', function(Entity $entity) use ($entityMap, $mapper) {
                
                $relationsToCascade = $entityMap->getCascadeDeletesOn();
                
                foreach($relationsToCascade as $relation) {

                    if ($relation instanceof BelongsTo) {
                        $primaryKey = $entityMap->getKeyName();
                        $foreignKey = $relation->getForeignKey();

                        $noOthersLeft = !$mapper
                                        ->where($foreignKey, '=', $entity->$foreignKey)
                                        ->where($primaryKey, '<>', $entity->$primaryKey)
                                        ->exists();

                        if ($noOthersLeft) {
                            $relatedMapper = $relation->getRelatedMapper();

                            $relatedMapper->delete($entity->$relation);
                        }
                    }

                    if ($relation instanceof BelongsToMany) {
                        $relatedMapper = $relation->getRelatedMapper();
                        $foreignKey = $relation->getForeignKey();
                        $primaryKey = $entityMap->getKeyName();
                        $otherKey = $relation->getOtherKey();

                        foreach($entity->$relation as $related) {
                            $noOthersLeft = !$relation->newPivotStatement()
                                            ->where($otherKey, '=', $related->$otherKey)
                                            ->where($foreignKey, '<>', $entity->$primaryKey)
                                            ->exists();

                            if ($noOthersLeft) {
                                $relatedMapper->delete($entity->$related);
                            }
                        }
                    }

                    if ($relation instanceof HasOneOrMany) {
                        $relatedMapper = $relation->getRelatedMapper();

                        foreach($entity->$relation as $related) $relatedMapper->delete($related);
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
