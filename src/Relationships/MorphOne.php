<?php

namespace Analogue\ORM\Relationships;

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
     * Match the eagerly loaded results to their parents.
     *
     * @param array  $results
     * @param string $relation
     *
     * @return array
     */
    public function match(array $results, $relation)
    {
        return $this->matchOne($results, $relation);
    }
}
