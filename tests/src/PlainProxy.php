<?php

namespace TestApp;

class PlainProxy
{
    protected $related;

    protected $related_id;

    protected $user_id;

    protected $user;

    protected $id;

    public function __construct($user, $related)
    {
        $this->related = $related;
        $this->user = $user;
    }

    public function getRelated()
    {
        return $this->related;
    }

    public function getId()
    {
        return $this->id;
    }
}
