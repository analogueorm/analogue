<?php

use TestApp\Car;
use TestApp\Vehicle;

class SingleTableInheritanceTest extends DomainTestCase
{
    /** @test */
    public function we_can_store_multiple_object_types_in_a_single_table()
    {
        $vehicle = new Vehicle();
        $vehicle->name = 'base vehicle';
        $vehicleMapper = $this->mapper($vehicle);
        $vehicleMapper->store($vehicle);
        $car = new Car();
        $car->name = 'car';
        $carMapper = $this->mapper($car);
        $carMapper->store($car);
        $this->seeInDatabase('vehicles', [
            'name' => 'base vehicle',
            'type' => 'vehicle',
        ]);
        $this->seeInDatabase('vehicles', [
            'name' => 'car',
            'type' => 'car',
        ]);
    }

    /** @test */
    public function we_can_query_multiple_object_types_from_base_mapper()
    {
        $this->rawInsert('vehicles', [
            'name' => 'car',
            'type' => 'car',
        ]);
        $this->rawInsert('vehicles', [
            'name' => 'base vehicle',
            'type' => 'vehicle',
        ]);
        $vehicleMapper = $this->mapper(Vehicle::class);
        $results = $vehicleMapper->get();
        $this->assertCount(2, $results);
        $result = $vehicleMapper->whereName('base vehicle')->first();
        $this->assertInstanceOf(Vehicle::class, $result);
        $result = $vehicleMapper->whereName('car')->first();
        $this->assertInstanceOf(Car::class, $result);
    }

    /** @test */
    public function inherited_mapper_only_return_discriminated_records()
    {
        $this->rawInsert('vehicles', [
            'name' => 'car',
            'type' => 'car',
        ]);
        $this->rawInsert('vehicles', [
            'name' => 'base vehicle',
            'type' => 'vehicle',
        ]);

        $carMapper = $this->mapper(Car::class);
        $results = $carMapper->get();
        $this->assertCount(1, $results);
    }
}
