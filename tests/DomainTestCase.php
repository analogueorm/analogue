<?php

use Carbon\Carbon;

abstract class DomainTestCase extends AnalogueTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->analogue->registerMapNamespace("TestApp\Maps");

        // All Classes taking part in STI must be explitely registered
        //
        $this->analogue->register(TestApp\Vehicle::class, TestApp\Maps\VehicleMap::class);
        $this->analogue->register(TestApp\Car::class, TestApp\Maps\CarMap::class);
    }

    /**
     * Insert a User using a raw database query.
     *
     * @return int The ID of the inserted user
     */
    protected function insertUser()
    {
        $faker = $this->faker();

        return $this->rawInsert('users', [
            'id'                 => $this->randId(),
            'name'               => $faker->name,
            'email'              => $faker->email,
            'password'           => bcrypt(str_random(30)),
            'identity_firstname' => $faker->firstName,
            'identity_lastname'  => $faker->lastName,
            'created_at'         => Carbon::now(),
            'updated_at'         => Carbon::now(),
        ]);
    }

    /**
     * Insert a Group using a raw database query.
     *
     * @return int The ID of the inserted group
     */
    protected function insertGroup()
    {
        $faker = $this->faker();

        return $this->rawInsert('groups', [
            'id'   => $this->randId(),
            'name' => $faker->sentence,
        ]);
    }
}
