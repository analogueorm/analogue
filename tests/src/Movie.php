<?php

namespace TestApp;

class Movie
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var string
     */
    protected $title;

    /**
     * @var Realisator;
     */
    protected $realisator;

    public function __construct($title)
    {
        $this->title = $title;
    }

    public function setRealisator(Realisator $realisator)
    {
        $this->realisator = $realisator;
    }

    public function getRealisator()
    {
        return $this->realisator;
    }
}
