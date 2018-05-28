<?php

use Analogue\ORM\EntityMap;
use TestApp\Car;
use TestApp\Maps\VehicleMap;
use TestApp\Vehicle;
use TestApp\Wheel;

class SingleTableInheritanceTest extends AnalogueTestCase
{
    public function setUp()
    {
        parent::setUp();
    }

    /** @test */
    public function we_can_store_multiple_object_types_in_a_single_table()
    {
        $this->analogue->register(TestApp\Vehicle::class, TestApp\Maps\VehicleMap::class);
        $this->analogue->register(TestApp\Car::class, TestApp\Maps\CarMap::class);
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
        $this->analogue->register(TestApp\Vehicle::class, TestApp\Maps\VehicleMap::class);
        $this->analogue->register(TestApp\Car::class, TestApp\Maps\CarMap::class);
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
        $this->analogue->register(TestApp\Vehicle::class, TestApp\Maps\VehicleMap::class);
        $this->analogue->register(TestApp\Car::class, TestApp\Maps\CarMap::class);
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

    /** @test */
    public function we_can_eager_load_relationship_from_inherited_entities()
    {
        $this->analogue->register(TestApp\Vehicle::class, TestApp\Maps\VehicleMap::class);
        $this->migrate('wheels', function ($table) {
            $table->increments('id');
            $table->integer('car_id');
            $table->integer('number');
        });
        $this->analogue->register(Car::class, new class() extends VehicleMap {
            public function wheels(Car $car)
            {
                return $this->hasMany($car, Wheel::class);
            }
        });
        $this->analogue->register(Wheel::class, new class() extends EntityMap {
        });
        $id = $this->rawInsert('vehicles', [
            'name' => 'car',
            'type' => 'car',
        ]);
        $this->assertDatabaseHas('vehicles', ['name' => 'car', 'id' => $id]);
        for ($x = 1; $x <= 4; $x++) {
            $this->rawInsert('wheels', [
                'car_id' => $id,
                'number' => $x,
            ]);
            $this->assertDatabaseHas('wheels', ['car_id' => $id, 'number' => $x]);
        }
        $cars = mapper(Vehicle::class)->with('wheels')->get();
        $this->assertCount(1, $cars);
        $car = $cars->first();
        $this->assertNotInstanceOf(\Analogue\ORM\System\Proxies\CollectionProxy::class, $car->wheels);
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $car->wheels);
        $this->assertCount(4, $car->wheels);
    }

    /** @test */
    public function eager_loaded_relationship_from_inherited_entities_are_only_loaded_once()
    {
        $this->analogue->register(TestApp\Vehicle::class, TestApp\Maps\VehicleMap::class);
        $this->migrate('wheels', function ($table) {
            $table->increments('id');
            $table->integer('car_id');
            $table->integer('number');
        });
        $this->analogue->register(Car::class, new class() extends VehicleMap {
            public function wheels(Car $car)
            {
                return $this->hasMany($car, Wheel::class);
            }
        });
        $this->analogue->register(Wheel::class, new class() extends EntityMap {
        });

        for ($x = 1; $x <= 10; $x++) {
            $id = $this->rawInsert('vehicles', [
                'name' => 'car',
                'type' => 'car',
            ]);
            $this->assertDatabaseHas('vehicles', ['name' => 'car', 'id' => $id]);
            for ($y = 1; $y <= 4; $y++) {
                $this->rawInsert('wheels', [
                    'car_id' => $id,
                    'number' => $y,
                ]);
                $this->assertDatabaseHas('wheels', ['car_id' => $id, 'number' => $y]);
            }
        }
        $cars = mapper(Vehicle::class)->with('wheels')->get();
        $this->assertCount(10, $cars);

        foreach ($cars as $car) {
            $this->assertNotInstanceOf(\Analogue\ORM\System\Proxies\CollectionProxy::class, $car->wheels);
            $this->assertInstanceOf(\Illuminate\Support\Collection::class, $car->wheels);
            $this->assertCount(4, $car->wheels);
        }
    }
}
