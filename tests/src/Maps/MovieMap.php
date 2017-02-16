<?php

namespace TestApp\Maps;

use Analogue\ORM\EntityMap;
use TestApp\Movie;
use TestApp\Realisator;

class MovieMap extends EntityMap
{
    protected $attributes = [
        'title',
        'realisator',
    ];

    public function realisator(Movie $movie)
    {
        return $this->belongsTo($movie, Realisator::class);
    }
}
