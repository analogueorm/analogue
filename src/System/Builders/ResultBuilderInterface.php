<?php

namespace Analogue\ORM\System\Builders;

interface ResultBuilderInterface
{
    /**
     * Convert a result set into an array of entities.
     *
     * @param array $results    The results to convert into entities.
     * @param array $eagerLoads Relationships to eagerly load for these results.
     *
     * @return mixed
     */
    public function build(array $results, array $eagerLoads);
}
