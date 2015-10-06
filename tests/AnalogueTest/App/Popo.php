<?php namespace AnalogueTest\App;

class Popo
{

    protected $id;

    protected $name;

    protected $user;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setUser(User $user)
    {
        $this->user = $user;
    }
}
