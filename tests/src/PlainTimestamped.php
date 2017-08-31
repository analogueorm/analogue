<?php

namespace TestApp;

class PlainTimestamped
{
    protected $id;

    protected $created_at;

    protected $updated_at;

    public function id()
    {
        return $this->id;
    }

    public function createdAt()
    {
        return $this->created_at;
    }

    public function updatedAt()
    {
        return $this->updated_at;
    }
}
