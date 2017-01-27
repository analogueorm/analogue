<?php

namespace Analogue\ORM\Drivers;

use Illuminate\Database\DatabaseManager;

class IlluminateConnectionProvider
{
    /**
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
     * @param  $name
     *
     * @return \Illuminate\Database\Connection
     */
    public function connection($name = null)
    {
        return $this->db->connection($name);
    }
}
