<?php

namespace Analogue\ORM\Drivers;

use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;

class IlluminateConnectionProvider
{
    /**
     * Database manager.
     *
     * @var DatabaseManager
     */
    protected $db;

    /**
     * IlluminateConnectionProvider constructor.
     *
     * @param DatabaseManager $db
     */
    public function __construct(DatabaseManager $db)
    {
        $this->db = $db;
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
        return $this->db->connection($name);
    }
}
