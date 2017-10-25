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
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'illuminate';
    }

    /**
     * {@inheritdoc}
     */
    public function getAdapter($connection = null)
    {
        $connection = $this->connectionProvider->connection($connection);

        return new IlluminateDBAdapter($connection);
    }
}
