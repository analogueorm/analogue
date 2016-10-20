<?php

use Carbon\Carbon;

abstract class DomainTestCase extends AnalogueTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->analogue->registerMapNamespace("TestApp\Maps");
    }

    /**
     * Insert a User using raw DB query
     * 
     * @return User
     */
    protected function insertUser()
    {
        $faker = $this->faker();
        return $this->rawInsert('users', [
            'id' => $this->randId(),
            'name' => $faker->name,
            'email' => $faker->email,
            'password' => bcrypt(str_random(30)),
            'identity_firstname' => $faker->firstName,
            'identity_lastname' => $faker->lastName,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }

}   


