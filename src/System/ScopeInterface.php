<?php

namespace Analogue\ORM\System;

interface ScopeInterface
{
    /**
     * Apply the scope to a given Query builder.
     *
     * @param \Analogue\ORM\System\Query $builder
     *
     * @return void
     */
    public function apply(Query $builder);

    /**
     * Remove the scope from the Query builder.
     *
     * @param \Analogue\ORM\System\Query $builder
     *
     * @return void
     */
    public function remove(Query $builder);
}
