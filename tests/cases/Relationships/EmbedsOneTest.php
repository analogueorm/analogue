<?php

use TestApp\Image;
use TestApp\ImageSize;
use TestApp\Maps\ImageMap;
use TestApp\Maps\ImageMapCustomMap;
use TestApp\Maps\ImageMapCustomPrefix;
use TestApp\Maps\ImageMapJson;
use TestApp\Maps\ImageMapNoPrefix;

class EmbedsOneTest extends DomainTestCase
{
    /** @test */
    public function we_can_store_an_embedded_object_with_default_mapping()
    {
        $this->analogue->register(Image::class, ImageMap::class);
        $image = $this->createImage();

        $mapper = $this->mapper($image);
        $mapper->store($image);
        $this->seeInDatabase('images', [
            'size_width'  => 500,
            'size_height' => 500,
        ]);
    }

    /** @test */
    public function we_can_store_an_embedded_object_with_no_prefix()
    {
        $this->analogue->register(Image::class, ImageMapNoPrefix::class);
        $image = $this->createImage();
        $mapper = $this->mapper($image);
        $mapper->store($image);
        $this->seeInDatabase('images', [
            'width'  => 500,
            'height' => 500,
        ]);
    }

    /** @test */
    public function we_can_store_an_embedded_object_with_custom_prefix()
    {
        $this->analogue->register(Image::class, ImageMapCustomPrefix::class);
        $image = $this->createImage();
        $mapper = $this->mapper($image);
        $mapper->store($image);
        $this->seeInDatabase('images', [
            'custom_width'  => 500,
            'custom_height' => 500,
        ]);
    }

    /** @test */
    public function we_can_store_an_embedded_object_with_custom_mapping()
    {
        $this->analogue->register(Image::class, ImageMapCustomMap::class);
        $image = $this->createImage();
        $mapper = $this->mapper($image);
        $mapper->store($image);
        $this->seeInDatabase('images', [
            'w' => 500,
            'h' => 500,
        ]);
    }

    /** @test */
    // Note available in Sqlite
    /*public function we_can_store_an_embedded_object_in_a_json_array()
    {
        $this->analogue->register(Image::class, ImageMapArray::class);
        $image = $this->createImage();
        $mapper = $this->mapper($image);
        $mapper->store($image);
        $this->seeInDatabase('images', [
            'size' => [
                'width' => 500,
                'height' => 500,
            ],
        ]);
    }*/

    /** @test */
    public function we_can_store_an_embedded_object_as_json()
    {
        $this->analogue->register(Image::class, ImageMapJson::class);
        $image = $this->createImage();
        $mapper = $this->mapper($image);
        $mapper->store($image);
        $this->seeInDatabase('images', [
            'size' => json_encode([
                'width'  => 500,
                'height' => 500,
            ]),
        ]);
    }

    /** @test */
    public function we_can_hydrate_embedded_object_with_default_mapping()
    {
        $this->analogue->register(Image::class, ImageMap::class);
        $id = $this->createImageRecord([
            'size_width'  => 500,
            'size_height' => 500,
        ]);
        $mapper = $this->mapper(Image::class);
        $image = $mapper->find($id);
        $this->assertInstanceOf(ImageSize::class, $image->getSize());
        $this->assertEquals(500, $image->getSize()->getHeight());
        $this->assertEquals(500, $image->getSize()->getWidth());
    }

    /** @test */
    public function we_can_hydrate_embedded_object_with_no_prefix_mapping()
    {
        $this->analogue->register(Image::class, ImageMapNoPrefix::class);
        $id = $this->createImageRecord([
            'width'  => 500,
            'height' => 500,
        ]);
        $mapper = $this->mapper(Image::class);
        $image = $mapper->find($id);
        $this->assertInstanceOf(ImageSize::class, $image->getSize());
        $this->assertEquals(500, $image->getSize()->getHeight());
        $this->assertEquals(500, $image->getSize()->getWidth());
    }

    /** @test */
    public function we_can_hydrate_embedded_object_with_custom_prefix_mapping()
    {
        $this->analogue->register(Image::class, ImageMapCustomPrefix::class);
        $id = $this->createImageRecord([
            'custom_width'  => 500,
            'custom_height' => 500,
        ]);
        $mapper = $this->mapper(Image::class);
        $image = $mapper->find($id);
        $this->assertInstanceOf(ImageSize::class, $image->getSize());
        $this->assertEquals(500, $image->getSize()->getHeight());
        $this->assertEquals(500, $image->getSize()->getWidth());
    }

    /** @test */
    public function we_can_hydrate_embedded_object_with_custom_mapping()
    {
        $this->analogue->register(Image::class, ImageMapCustomMap::class);
        $id = $this->createImageRecord([
            'w' => 500,
            'h' => 500,
        ]);
        $mapper = $this->mapper(Image::class);
        $image = $mapper->find($id);

        $this->assertInstanceOf(ImageSize::class, $image->getSize());
        $this->assertEquals(500, $image->getSize()->getHeight());
        $this->assertEquals(500, $image->getSize()->getWidth());
    }

    // NOT AVAILABLE IN SQLITE
    /*public function we_can_hydrate_embedded_object_with_array_mapping()
    {
        $this->analogue->register(Image::class, ImageMapArray::class);
        $id = $this->createImageRecord([
            'size' => ['width' => 500, 'height' => 500]
        ]);
        $mapper = $this->mapper(Image::class);
        $image = $mapper->find($id);
        $this->assertInstanceOf(ImageSize::class, $image->getSize());
        $this->assertEquals(500, $image->getSize()->getHeight());
        $this->assertEquals(500, $image->getSize()->getWidth());
    }*/

    /** @test */
    public function we_can_hydrate_embedded_object_with_json_mapping()
    {
        $this->analogue->register(Image::class, ImageMapJson::class);
        $id = $this->createImageRecord([
            'size' => json_encode(['width' => 500, 'height' => 500]),
        ]);
        $mapper = $this->mapper(Image::class);
        $image = $mapper->find($id);
        $this->assertInstanceOf(ImageSize::class, $image->getSize());
        $this->assertEquals(500, $image->getSize()->getHeight());
        $this->assertEquals(500, $image->getSize()->getWidth());
    }

    /** @test */
    public function embedded_object_attributes_get_updated_if_dirty()
    {
        $this->analogue->register(Image::class, ImageMap::class);
        $image = $this->createImage();
        $mapper = $this->mapper($image);
        $mapper->store($image);
        $image->setSize(new ImageSize(1000, 1000));
        $mapper->store($image);
        $this->seeInDatabase('images', [
            'size_width'  => 1000,
            'size_height' => 1000,
        ]);
    }

    /** @test */
    public function embedded_object_attributes_get_nulled_if_relationshiped_nulled()
    {
        $this->analogue->register(Image::class, ImageMap::class);
        $image = $this->createImage();
        $mapper = $this->mapper($image);
        $mapper->store($image);
        $image->setNullSize();
        $mapper->store($image);
        $this->seeInDatabase('images', [
            'size_width'  => null,
            'size_height' => null,
        ]);
    }

    protected function dumpImages()
    {
        $images = $this->db()->table('images')->get();
        dump($images);
    }

    protected function createImageRecord(array $size)
    {
        $id = $this->db()->table('images')->insertGetId(array_merge(
            ['url' => 'some url'], $size));

        return $id;
    }

    protected function createImage()
    {
        $size = new ImageSize(500, 500);
        $url = 'some url';

        return new Image($url, $size);
    }
}
