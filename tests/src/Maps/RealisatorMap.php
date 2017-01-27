<?php

namespace TestApp\Maps;

use Analogue\ORM\EntityMap;
use TestApp\Movie;
use TestApp\Realisator;

class RealisatorMap extends EntityMap
{
    protected $attributes = [
        'name',
    ];

    public function movies(Realisator $realisator)
    {
        return $this->hasMany($realisator, Movie::class);
    }
}
