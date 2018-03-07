<?php

use TestApp\Maps\SettingsMap;
use TestApp\Option;
use TestApp\Settings;

class EmbedsManyTest extends DomainTestCase
{
    /** @test */
    public function we_can_store_embedded_objects_as_json()
    {
        $this->analogue->register(Settings::class, SettingsMap::class);
        $settings = $this->createSettings();

        $mapper = $this->mapper($settings);
        $mapper->store($settings);

        $this->seeInDatabase('settings', [
            'options' => json_encode([
                ['label' => 'test1', 'value' => 'value1'],
            ]),
        ]);
    }

    /** @test */
    public function we_can_hydrate_embedded_object_with_array_mapping()
    {
        $this->analogue->register(Settings::class, SettingsMap::class);
        $id = $this->createSettingsRecord([
            'options' => json_encode([
                ['label' => 'test1', 'value' => 'value1'],
            ]),
        ]);

        $mapper = $this->mapper(Settings::class);
        $settings = $mapper->find($id);
        $this->assertCount(1, $settings->options);
        $option = $settings->options->first();
        $this->assertInstanceOf(Option::class, $option);
        $this->assertEquals('test1', $option->label);
        $this->assertEquals('value1', $option->value);
    }

    protected function createSettingsRecord(array $attributes)
    {
        return $this->db()->table('settings')->insertGetId($attributes);
    }

    protected function createSettings()
    {
        $option = new Option('test1', 'value1');

        return new Settings([$option]);
    }
}
