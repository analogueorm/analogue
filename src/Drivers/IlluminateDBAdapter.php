<?php

namespace Analogue\ORM\Drivers;

use Illuminate\Database\Connection;

/**
 * Illuminate Driver for Analogue ORM. If multiple DB connections are
 * involved, we'll treat each underlyin driver as a separate instance.
 */
class IlluminateDBAdapter implements DBAdapter
{
    /**
     * @var Connection
     */
    protected $connection;

    /**
     * IlluminateDBAdapter constructor.
     *
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Return a new Query instance for this driver.
     *
     * @return QueryAdapter
     */
    public function getQuery()
    {
        $connection = $this->connection;

        $grammar = $connection->getQueryGrammar();

        return new IlluminateQueryBuilder($connection, $grammar, $connection->getPostProcessor());
    }

    /**
     * Get the date format supported by the current connection.
     *
     * @return string
     */
    public function getDateFormat()
    {
        return $this->connection->getQueryGrammar()->getDateFormat();
    }

    /**
     * Start a DB transaction on driver that supports it.
     *
     * @return void
     */
    public function beginTransaction()
    {
        $this->connection->beginTransaction();
    }

    /**
     * Commit a DB transaction on driver that supports it.
     *
     * @return void
     */
    public function commit()
    {
        $this->connection->commit();
    }

    /**
     * Rollback a DB transaction.
     *
     * @return void
     */
    public function rollback()
    {
        $this->connection->rollBack();
    }
}
