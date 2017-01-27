<?php

namespace Analogue\ORM\Drivers;

class IlluminateDriver implements DriverInterface
{
    /**
     * The Illuminate Connection Provider.
     *
     * @var CapsuleConnectionProvider|IlluminateConnectionProvider
     */
    protected $connectionProvider;

    /**
     * IlluminateDriver constructor.
     *
     * @param $connectionProvider
     */
    public function __construct($connectionProvider)
    {
        $this->connectionProvider = $connectionProvider;
    }

    /**
     * Return the name of the driver.
     *
     * @return string
     */
    public function getName()
    {
        return 'illuminate';
    }

    /**
     * Get Analogue DBAdapter.
     *
     * @param string|null $connection
     *
     * @return IlluminateDBAdapter
     */
    public function getAdapter($connection = null)
    {
        $connection = $this->connectionProvider->connection($connection);

        return new IlluminateDBAdapter($connection);
    }
}
