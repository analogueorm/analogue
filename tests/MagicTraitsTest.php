<?php

use TestApp\Stubs\MagicEntity;

class MagicTraitsTest extends AnalogueTestCase
{
    /** @test */
    public function we_can_magically_get_object_properties()
    {
        $entity = new MagicEntity();

        $this->assertEquals('Some Value', $entity->classProperty);
    }

    /** @test */
    public function we_can_magically_get_attributes_from_array()
    {
        $entity = new MagicEntity();

        $this->assertEquals('Some Value', $entity->attr1);
        $this->assertEquals('Some Value', $entity->attr2);
    }

    /** @test */
    public function undefined_attribute_returns_null()
    {
        $entity = new MagicEntity();

        $this->assertNull($entity->someUnexistingAttribute);
    }

    /** @test */
    public function we_can_magically_set_unexisting_attributes()
    {
        $entity = new MagicEntity();
        $entity->name = 'test';
        $this->assertEquals('test', $entity->name);
    }

    /** @test */
    public function we_can_magically_set_existing_attributes()
    {
        $entity = new MagicEntity();
        $entity->attr1 = 'some other value';
        $this->assertEquals('some other value', $entity->attr1);
    }

    /** @test */
    public function we_can_cast_to_array()
    {
        $entity = new MagicEntity();
        $array = $entity->toArray();
        $this->assertEquals([
            'attr1' => 'Some Value',
            'attr2' => 'Some Value',
        ], $array);
    }

    /** @test */
    public function we_can_cast_to_json()
    {
        $entity = new MagicEntity();
        $json = $entity->toJson();
        $this->assertEquals([
            'attr1' => 'Some Value',
            'attr2' => 'Some Value',
        ], json_decode($json, true));
    }
}
