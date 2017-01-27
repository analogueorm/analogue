<?php

namespace TestApp\Maps;

use Analogue\ORM\EntityMap;

class VehicleMap extends EntityMap
{
    /**
     * @var string
     */
    protected $table = 'vehicles';

    /**
     * @var string
     */
    protected $inheritanceType = 'single_table';

    /**
     * @var string
     */
    protected $discriminatorColumn = 'type';

    /**
     * @var array
     */
    protected $discriminatorColumnMap = [
        'vehicle' => "TestApp\Vehicle",
        'car'     => "TestApp\Car",
    ];
}
