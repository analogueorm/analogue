<?php

namespace Analogue\ORM\Relationships;

class HasMany extends HasOneOrMany
{
    /**
     * Lazy-Load the results of the relationship.
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
     * Match the eagerly loaded results to their parents.
     *
     * @param array  $results
     * @param string $relation
     *
     * @return array
     */
    public function match(array $results, $relation)
    {
        return $this->matchMany($results, $relation);
    }
}
