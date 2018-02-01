<?php

namespace Analogue\ORM\Drivers;

interface DBAdapter
{
    /**
     * Return's Driver specific Query Implementation.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function getQuery();

    /**
     * Return the Date format used on this adapter.
     *
     * @return string
     */
    public function getDateFormat(): string;

    /**
     * Start a DB transaction on driver that supports it.
     *
     * @return void
     */
    public function beginTransaction();

    /**
     * Commit a DB transaction on driver that supports it.
     *
     * @return void
     */
    public function commit();

    /**
     * Rollback a DB transaction on driver that supports it.
     *
     * @return void
     */
    public function rollback();

    /**
     * Parse result from database and return an array of results
     * casted to analogue compatible values.
     *
     * @param array $rows
     *
     * @return array
     */
    public function fromDatabase(array $rows): array;
}
