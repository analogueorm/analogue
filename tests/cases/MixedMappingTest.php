<?php

use TestApp\MixedEntity;

class MixedMappingTest extends DomainTestCase
{
    /** @test */
    public function we_can_hydrates_both_attributes_array_and_properties()
    {
        $entity = new MixedEntity();
        $entity->setProperty('propertyValue');
        $entity->some_attribute = 'attributeValue';

        $mapper = $this->mapper(MixedEntity::class);
        $mapper->store($entity);

        $this->seeInDatabase('mixed_entities', [
            'property'       => 'propertyValue',
            'some_attribute' => 'attributeValue',
        ]);

        $loadedEntity = $mapper->find($entity->id);

        $this->assertEquals('propertyValue', $loadedEntity->getProperty());
        $this->assertEquals('attributeValue', $loadedEntity->some_attribute);
    }
}
