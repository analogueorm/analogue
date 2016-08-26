<?php

namespace Analogue\ORM\System;

use Analogue\ORM\System\Mapper;

class ResultBuilder
{
    /**
     * An array of entity builder instances used to hydrate a result set.
     *
     * @var array
     */
    protected $entityBuilders = [];

    /**
     * The mapper for the entity to build
     * @var \Analogue\ORM\System\Mapper
     */
    protected $mapper;

    /**
     * The Entity Map for the entity to build.
     *
     * @var \Analogue\ORM\EntityMap
     */
    protected $entityMap;

    /**
     * Relations that will be eager loaded on this query
     *
     * @var array
     */
    protected $eagerLoads;

    /**
     * An array of builders used by this class to build necessary
     * entities for each result type.
     *
     * @var array
     */
    protected $builders = [];

    /**
     * @param Mapper $mapper
     * @param array  $eagerLoads
     */
    public function __construct(Mapper $mapper, array $eagerLoads)
    {
        $this->mapper = $mapper;

        $this->eagerLoads = $eagerLoads;

        $this->entityMap = $mapper->getEntityMap();
    }

    /**
     * Convert a result set into an array of entities
     *
     * @param  array $results
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
     * @param  array $results
     * @return Collection
     */
    protected function buildWithDefaultMapper($results)
    {
        $builder = new EntityBuilder($this->mapper, array_keys($this->eagerLoads));

        return collect($results)->map(function($item, $key) use ($builder) {
            return $builder->build((array) $item);
        })->all();
    }

    /**
     * Build an entity from results, using single table inheritance.
     *
     * @param  array $results
     * @return Collection
     */
    protected function buildUsingSingleTableInheritance($results)
    {
        return collect($results)->map(function($item, $key) {
            $builder = $this->builderForResult((array) $item);

            return $builder->build((array) $item);
        })->all();
    }

    /**
     * Given a result array, return the entity builder needed to correctly
     * build the result into an entity.
     *
     * @param  array  $result
     * @return EntityBuilder
     */
    protected function builderForResult(array $result) : EntityBuilder
    {
        $type = $result[$this->entityMap->getDiscriminatorColumn()];
        $class = $this->entityMap->getDiscriminatorColumnMap()[$type];

        if (!isset($this->builders[$type])) {
            $manager = app('analogue');
            $this->builders[$type] = new EntityBuilder($manager->mapper($class), array_keys($this->eagerLoads));
        }

        return $this->builders[$type];
    }
}