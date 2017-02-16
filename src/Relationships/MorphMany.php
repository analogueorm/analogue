<?php

namespace Analogue\ORM\Relationships;

use Analogue\ORM\EntityCollection;

class MorphMany extends MorphOneOrMany
{
    /**
     * Get the results of the relationship.
     *
     * @param  $relation
     *
     * @return mixed
     */
    public function getResults($relation)
    {
        $results = $this->query->get();

        $this->cacheRelation($results, $relation);

        return $results;
    }

    /**
     * Initialize the relation on a set of models.
     *
     * @param array  $entities
     * @param string $relation
     *
     * @return array
     */
    public function initRelation(array $entities, $relation)
    {
        foreach ($entities as $entity) {
            $entity = $this->factory->make($entity);

            $entity->setEntityAttribute($relation, $this->relatedMap->newCollection());
        }

        return $entities;
    }

    /**
     * Match the eagerly loaded results to their parents.
     *
     * @param array            $entities
     * @param EntityCollection $results
     * @param string           $relation
     *
     * @return array
     */
    public function match(array $entities, EntityCollection $results, $relation)
    {
        return $this->matchMany($entities, $results, $relation);
    }
}
