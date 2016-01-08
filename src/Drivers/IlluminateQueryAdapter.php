<?php

namespace Analogue\ORM\Drivers;

use Illuminate\Database\Query\Builder;

/**
 * Class IlluminateQueryAdapter
 * @package Analogue\ORM\Drivers
 *
 * @mixin Builder
 */
class IlluminateQueryAdapter implements QueryAdapter
{
    /**
     * @var Builder
     */
    protected $query;

    /**
     * IlluminateQueryAdapter constructor.
     * @param Builder $query
     */
    public function __construct(Builder $query)
    {
        $this->query = $query;
    }

    /**
     * @param $method
     * @param $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return call_user_func_array([$this->query, $method], $parameters);
    }
}
