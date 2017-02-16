<?php

namespace Analogue\ORM\Relationships;

use Analogue\ORM\EntityCollection;

class MorphOne extends MorphOneOrMany
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
        $result = $this->query->first();

        $this->cacheRelation($result, $relation);

        return $result;
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

            $entity->setEntityAttribute($relation, null);
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
        return $this->matchOne($entities, $results, $relation);
    }
}
