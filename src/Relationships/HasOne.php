<?php

namespace Analogue\ORM\Relationships;

class HasOne extends HasOneOrMany
{
    /**
     * Get the results of the relationship.
     *
     * @param $relation
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
     * Get the results of the relationship.
     *
     * @return mixed
     */
    public function fetch()
    {
        return $this->query->first();
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
