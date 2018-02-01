<?php

namespace Analogue\ORM\Drivers;

class Manager
{
    /**
     * Drivers.
     *
     * @var DriverInterface[]
     */
    protected $drivers = [];

    /**
     * Add a Mapping Driver.
     *
     * @param DriverInterface $driver
     *
     * @return void
     */
    public function addDriver(DriverInterface $driver)
    {
        $this->drivers[$driver->getName()] = $driver;
    }

    /**
     * Get the DBAdapter.
     *
     * @param string $driver
     * @param string $connection connection name for drivers supporting multiple connection.
     *
     * @return DBAdapter
     */
    public function getAdapter(string $driver, string $connection = null)
    {
        if (array_key_exists($driver, $this->drivers)) {
            return $this->drivers[$driver]->getAdapter($connection);
        }
    }
}
