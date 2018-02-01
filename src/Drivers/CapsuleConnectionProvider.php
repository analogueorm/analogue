<?php

namespace Analogue\ORM\Drivers;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Connection;

class CapsuleConnectionProvider
{
    /**
     * Capsule manager.
     *
     * @var Capsule
     */
    protected $capsule;

    /**
     * CapsuleConnectionProvider constructor.
     *
     * @param Capsule $capsule
     */
    public function __construct(Capsule $capsule)
    {
        $this->capsule = $capsule;
    }

    /**
     * Get a Database connection object.
     *
     * @param string $name
     *
     * @return \Illuminate\Database\Connection
     */
    public function connection(string $name = null): Connection
    {
        return $this->capsule->getConnection($name);
    }
}
