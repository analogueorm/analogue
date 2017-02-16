<?php

namespace TestApp;

use Illuminate\Support\Collection;

class Realisator
{
    /**
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var Realisator;
     */
    protected $movies;

    public function __construct($name)
    {
        $this->name = $name;
        $this->movies = new Collection();
    }

    public function addMovie(Movie $movie)
    {
        $this->movies->push($movie);
    }

    public function getName()
    {
        return $this->name;
    }
}
