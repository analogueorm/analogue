<?php

namespace Analogue\ORM\Drivers;

interface DBAdapter
{
    /**
     * Return's Driver specific Query Implementation.
     *
     * @return \Analogue\ORM\Drivers\QueryAdapter|\Analogue\ORM\Drivers\IlluminateQueryAdapter
     */
    public function getQuery();

    /**
     * Return the Date format used on this adapter.
     *
     * @return string
     */
    public function getDateFormat();

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
}
