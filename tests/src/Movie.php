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
     * @var string
     */
    protected $someText;

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

    public function setSomeText(string $someText)
    {
        $this->someText = $someText;
    }

    public function getSomeText()
    {
        return $this->someText;
    }

    public function getId()
    {
        return $this->id;
    }
}
