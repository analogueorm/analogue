<?php

use TestApp\Stubs\MagicEntity;

class MagicTraitsTest extends AnalogueTestCase
{
	/** @test */
	public function we_can_magically_get_object_properties()
	{
		$entity = new MagicEntity;

		$this->assertEquals("Some Value" , $entity->classProperty);
	}

	/** @test */
	public function we_can_magically_get_attributes_from_array()
	{
		$entity = new MagicEntity;

		$this->assertEquals("Some Value" , $entity->attr1);
		$this->assertEquals("Some Value" , $entity->attr2);
	}
	
	/** @test */
	public function undefined_attribute_returns_null()
	{
		$entity = new MagicEntity;

		$this->assertNull($entity->someUnexistingAttribute);

	}
}
