<?php

namespace Analogue\ORM\System;

use Analogue\ORM\Drivers\Manager as DriverManager;
use Analogue\ORM\EntityMap;
use Illuminate\Contracts\Events\Dispatcher;

/**
 * Build a mapper instance from an EntityMap object, doing the
 * required parsing of relationships. Abstracting to this class
 * will make it easy to later cache the EntityMap for better performances.
 */
class MapperFactory
{
    /**
     * Manager instance.
     *
     * @var \Analogue\ORM\System\Manager
     */
    protected $manager;

    /**
     * DriverManager instance.
     *
     * @var \Analogue\ORM\Drivers\Manager
     */
    protected $drivers;

    /**
     * Event dispatcher instance.
     *
     * @var \Illuminate\Contracts\Events\Dispatcher
     */
    protected $dispatcher;

    /**
     * MapperFactory constructor.
     *
     * @param DriverManager $drivers
     * @param Dispatcher    $dispatcher
     * @param Manager       $manager
     */
    public function __construct(DriverManager $drivers, Dispatcher $dispatcher, Manager $manager)
    {
        $this->drivers = $drivers;

        $this->dispatcher = $dispatcher;

        $this->manager = $manager;
    }

    /**
     * Return a new Mapper instance.
     *
     * @param string    $entityClass
     * @param EntityMap $entityMap
     *
     * @return Mapper
     */
    public function make($entityClass, EntityMap $entityMap)
    {
        $driver = $entityMap->getDriver();

        $connection = $entityMap->getConnection();

        $adapter = $this->drivers->getAdapter($driver, $connection);

        $entityMap->setDateFormat($adapter->getDateFormat());

        $mapper = new Mapper($entityMap, $adapter, $this->dispatcher, $this->manager);

        // Fire Initializing Event
        $mapper->fireEvent('initializing', $mapper);

        // Proceed necessary parsing on the EntityMap object
        if (!$entityMap->isBooted()) {
            $entityMap->initialize();
        }

        // Apply Inheritance scope, if necessary
        if ($entityMap->getInheritanceType() == 'single_table') {
            $scope = new SingleTableInheritanceScope($entityMap);
            $mapper->addGlobalScope($scope);
        }

        // Fire Initialized Event
        $mapper->fireEvent('initialized', $mapper);

        return $mapper;
    }
}
