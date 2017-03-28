<?php

use TestApp\Timestamped;
use TestApp\PlainTimestamped;

class TimestampsTest extends DomainTestCase
{
    /** @test */
    public function timestamps_are_automatically_saved_on_entity()
    {
        $object = new Timestamped;
        $mapper = $this->mapper($object);

        $mapper->store($object);

        $this->assertNotNull($object->updated_at);
        $this->assertNotNull($object->created_at);
    }

    /** @test */
    public function timestamps_are_automatically_saved_on_object()
    {
        $object = new PlainTimestamped;
        $mapper = $this->mapper($object);

        $mapper->store($object);

        $this->assertNotNull($object->updatedAt());
        $this->assertNotNull($object->createdAt());
    }
}
