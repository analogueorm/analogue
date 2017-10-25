<?php

namespace Analogue\ORM\Commands;

use Analogue\ORM\System\Aggregate;
use Illuminate\Database\Query\Builder;

abstract class Command
{
    /**
     * The aggregated entity on which the command is executed.
     *
     * @var \Analogue\ORM\System\Aggregate
     */
    protected $aggregate;

    /**
     * Query Builder instance.
     *
     * @var \Illuminate\Database\Query\Builder
     */
    protected $query;

    /**
     * Command constructor.
     *
     * @param Aggregate                          $aggregate
     * @param \Illuminate\Database\Query\Builder $query
     */
    public function __construct(Aggregate $aggregate, Builder $query)
    {
        $this->aggregate = $aggregate;

        $this->query = $query->from($aggregate->getEntityMap()->getTable());
    }

    abstract public function execute();
}
