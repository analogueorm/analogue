<?php

class EventTest extends AnalogueTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->analogue->registerMapNamespace("TestApp\Maps");
    }

    /** @test */
    public function global_events_are_fired()
    {
    }
}
