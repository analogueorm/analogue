<?php

namespace Analogue\ORM\Drivers;

class Manager
{
    /**
     * @var DriverInterface[]
     */
    protected $drivers = [];

    /**
     * Add a Mapping Driver.
     *
     * @param DriverInterface $driver
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
     * @return DriverInterface|void
     */
    public function getAdapter($driver, $connection = null)
    {
        if (array_key_exists($driver, $this->drivers)) {
            return $this->drivers[$driver]->getAdapter($connection);
        }
    }
}
