<?php

use Carbon\Carbon;
use TestApp\PlainTimestamped;
use TestApp\Timestamped;

class TimestampsTest extends DomainTestCase
{
    /** @test */
    public function timestamps_are_automatically_saved_on_entity()
    {
        $object = new Timestamped();
        $mapper = $this->mapper($object);

        $mapper->store($object);

        $this->assertNotNull($object->updated_at);
        $this->assertNotNull($object->created_at);
    }

    /** @test */
    public function timestamps_are_only_set_if_attribute_is_null()
    {
        $object = new Timestamped();
        $object->created_at = new Carbon('1970-01-01');
        $object->updated_at = new Carbon('1970-01-01');
        $mapper = $this->mapper($object);

        $mapper->store($object);
        $this->assertEquals('1970-01-01 00:00:00', $object->updated_at->__toString());
        $this->assertEquals('1970-01-01 00:00:00', $object->created_at->__toString());
    }

    /** @test */
    public function timestamps_are_automatically_saved_on_plain_php_object()
    {
        $object = new PlainTimestamped();
        $mapper = $this->mapper($object);

        $mapper->store($object);

        $this->assertNotNull($object->updatedAt());
        $this->assertNotNull($object->createdAt());
    }
}
