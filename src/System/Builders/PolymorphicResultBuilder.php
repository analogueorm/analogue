<?php

namespace Analogue\ORM\System\Builders;

use Analogue\ORM\System\Manager;
use Analogue\ORM\System\Mapper;

class PolymorphicResultBuilder implements ResultBuilderInterface
{
    /**
     * The default mapper used to build entities with.
     *
     * @var \Analogue\ORM\System\Mapper
     */
    protected $defaultMapper;

    /**
     * Reference to all mappers used in this result set.
     *
     * @var array
     */
    protected $mappers = [];

    /**
     * Relations that will be eager loaded on this query.
     *
     * @var array
     */
    protected $eagerLoads;

    /**
     * The Entity Map for the entity to build.
     *
     * @var \Analogue\ORM\EntityMap
     */
    protected $entityMap;

    /**
     * An array of builders used by this class to build necessary
     * entities for each result type.
     *
     * @var array
     */
    protected $builders = [];

    /**
     * ResultBuilder constructor.
     *
     * @param Mapper $defaultMapper
     */
    public function __construct(Mapper $defaultMapper)
    {
        $this->defaultMapper = $defaultMapper;
        $this->entityMap = $defaultMapper->getEntityMap();
    }

    /**
     * Convert a result set into an array of entities.
     *
     * @param array $results
     * @param array $eagerLoads name of the relation(s) to be eager loaded on the Entities
     *
     * @return array
     */
    public function build(array $results, array $eagerLoads)
    {
        // Make a list of all primary key of the current result set. This will
        // allow us to group all polymorphic operations by type, then put
        // back every object in the intended order.
        $primaryKeyColumn = $this->entityMap->getKeyName();
        $ids = array_map(function ($row) use ($primaryKeyColumn) {
            return $row[$primaryKeyColumn];
        }, $results);

        $results = array_combine($ids, $results);

        // Make a list of types appearing within this result set.
        $discriminatorColumn = $this->entityMap->getDiscriminatorColumn();
        $types = array_unique(array_pluck($results, $discriminatorColumn));

        // We'll split the result set by type that will make it easier to deal
        // with.
        $entities = [];

        foreach ($types as $type) {
            $this->mappers[$type] = $this->getMapperForType($type);

            $resultsByType[$type] = array_filter($results, function (array $row) use ($type, $discriminatorColumn) {
                return $row[$discriminatorColumn] === $type;
            });

            $entities = $entities + $this->buildResultsForType($resultsByType[$type], $type, $eagerLoads);
        }

        return array_map(function ($id) use ($entities) {
            return $entities[$id];
        }, $ids);
    }

    protected function buildResultsForType($results, $type, array $eagerLoads)
    {
        $builder = new ResultBuilder($this->mappers[$type]);

        return $builder->build($results, $eagerLoads);
    }

    protected function getMapperForType(string $type): Mapper
    {
        $columnMap = $this->entityMap->getDiscriminatorColumnMap();

        $class = isset($columnMap[$type]) ? $columnMap[$type] : $type;

        return Manager::getInstance()->mapper($class);
    }
}
