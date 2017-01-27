<?php

namespace Analogue\ORM\System;

class ResultBuilder
{
    /**
     * An instance of the entity manager class.
     *
     * @var \Analogue\ORM\System\Manager
     */
    protected $manager;

    /**
     * The default mapper used to build entities with.
     *
     * @var \Analogue\ORM\System\Mapper
     */
    protected $defaultMapper;

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
     * @param Manager $manager
     * @param Mapper  $defaultMapper
     * @param array   $eagerLoads
     */
    public function __construct(Manager $manager, Mapper $defaultMapper, array $eagerLoads)
    {
        $this->manager = $manager;

        $this->defaultMapper = $defaultMapper;

        $this->eagerLoads = $eagerLoads;

        $this->entityMap = $defaultMapper->getEntityMap();
    }

    /**
     * Convert a result set into an array of entities.
     *
     * @param array $results
     *
     * @return \Illuminate\Support\Collection
     */
    public function build($results)
    {
        switch ($this->entityMap->getInheritanceType()) {
            case 'single_table':
                return $this->buildUsingSingleTableInheritance($results);
                break;

            default:
                return $this->buildWithDefaultMapper($results);
                break;
        }
    }

    /**
     * Build an entity from results, using the default mapper on this builder.
     * This is the default build plan when no table inheritance is being used.
     *
     * @param array $results
     *
     * @return Collection
     */
    protected function buildWithDefaultMapper($results)
    {
        $builder = new EntityBuilder($this->defaultMapper, array_keys($this->eagerLoads));

        return collect($results)->map(function ($item, $key) use ($builder) {
            return $builder->build((array) $item);
        })->all();
    }

    /**
     * Build an entity from results, using single table inheritance.
     *
     * @param array $results
     *
     * @return Collection
     */
    protected function buildUsingSingleTableInheritance($results)
    {
        return collect($results)->map(function ($item, $key) {
            $builder = $this->builderForResult((array) $item);

            return $builder->build((array) $item);
        })->all();
    }

    /**
     * Given a result array, return the entity builder needed to correctly
     * build the result into an entity. If no getDiscriminatorColumnMap property
     * has been defined on the EntityMap, we'll assume that the value stored in
     * the $type column is the fully qualified class name of the entity and
     * we'll use it instead.
     *
     * @param array $result
     *
     * @return EntityBuilder
     */
    protected function builderForResult(array $result)
    {
        $type = $result[$this->entityMap->getDiscriminatorColumn()];

        $columnMap = $this->entityMap->getDiscriminatorColumnMap();

        $class = isset($columnMap[$type]) ? $columnMap[$type] : $type;

        if (!isset($this->builders[$type])) {
            $this->builders[$type] = new EntityBuilder($this->manager->mapper($class), array_keys($this->eagerLoads));
        }

        return $this->builders[$type];
    }
}
