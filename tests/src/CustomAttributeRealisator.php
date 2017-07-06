<?php

namespace TestApp;

class CustomAttributeRealisator
{
    /**
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    protected $realisatorName;

    public function __construct($name)
    {
        $this->realisatorName = $name;
    }

    public function getName()
    {
        return $this->realisatorName;
    }
}
