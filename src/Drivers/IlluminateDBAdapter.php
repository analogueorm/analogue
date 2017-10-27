<?php

namespace Analogue\ORM\Drivers;

use Illuminate\Database\Connection;

/**
 * Illuminate Driver for Analogue ORM. If multiple DB connections are
 * involved, we'll treat each underlying driver as a separate instance.
 */
class IlluminateDBAdapter implements DBAdapter
{
    /**
     * Database connection.
     *
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
     * {@inheritdoc}
     */
    public function getQuery()
    {
        $connection = $this->connection;

        $grammar = $connection->getQueryGrammar();

        return new IlluminateQueryBuilder($connection, $grammar, $connection->getPostProcessor());
    }

    /**
     * {@inheritdoc}
     */
    public function getDateFormat(): string
    {
        return $this->connection->getQueryGrammar()->getDateFormat();
    }

    /**
     * {@inheritdoc}
     */
    public function beginTransaction()
    {
        $this->connection->beginTransaction();
    }

    /**
     * {@inheritdoc}
     */
    public function commit()
    {
        $this->connection->commit();
    }

    /**
     * {@inheritdoc}
     */
    public function rollback()
    {
        $this->connection->rollBack();
    }

    /**
     * {@inheritdoc}
     */
    public function fromDatabase(array $rows): array
    {
        return $rows;
    }
}
