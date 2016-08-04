<?php

class ProxyTest extends AnalogueTestCase 
{
    public function setUp()
    {
        parent::setUp();
        $this->analogue->registerMapNamespace("TestApp\Maps");
    }

    /** @test */
    public function we_can_load_a_relation_from_a_proxy()
    {
        
    }

}
