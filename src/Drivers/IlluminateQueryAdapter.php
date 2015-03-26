<?php namespace Analogue\ORM\Drivers;

use Illuminate\Database\Query\Builder;

class IlluminateQueryAdapter implements QueryAdapter {

    protected $query;

    public function __construct(Builder $query)
    {
        $this->query = $query;
    }

    public function __call($method, $parameters)
    {
        return call_user_func_array(array($this->query, $method), $parameters);
    }

}
