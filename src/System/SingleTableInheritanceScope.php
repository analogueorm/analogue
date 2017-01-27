<?php

namespace Analogue\ORM\System;

use Analogue\ORM\EntityMap;

class SingleTableInheritanceScope implements ScopeInterface
{
    /**
     * Discriminator column name.
     *
     * @var string
     */
    protected $column;

    /**
     * Discriminator column allowed types.
     *
     * @var array
     */
    protected $types = [];

    public function __construct(EntityMap $entityMap)
    {
        // Putting the heavy logic in here, so we won't have
        // to go through it each time we reach for a query
        // builder.

        $this->column = $entityMap->getDiscriminatorColumn();

        // First we need to retrieve the base class & it's normalized
        // type string
        $class = $entityMap->getClass();
        $this->types[] = $this->getTypeStringForEntity($class, $entityMap);

        // Then, we parse all registered entities for any potential
        // child class.
        $classes = Manager::getInstance()->getRegisteredEntities();

        foreach ($classes as $otherClass => $entityMap) {
            if (is_subclass_of($otherClass, $class)) {
                $this->types[] = $this->getTypeStringForEntity($otherClass, $entityMap);
            }
        }
    }

    /**
     * Get the normalized value to use for query on discriminator column.
     *
     * @param string    $class
     * @param EntityMap $entityMap
     *
     * @return string
     */
    protected function getTypeStringForEntity($class, EntityMap $entityMap)
    {
        $class = $entityMap->getClass();

        $type = array_keys(
            $entityMap->getDiscriminatorColumnMap(),
            $class
        );

        if (count($type) == 0) {
            return $class;
        }

        return $type[0];
    }

    /**
     * Apply the scope to a given Analogue query builder.
     *
     * @param \Analogue\ORM\System\Query $query
     *
     * @return void
     */
    public function apply(Query $query)
    {
        $query->whereIn($this->column, $this->types);
    }

    /**
     * Remove the scope from the given Analogue query builder.
     *
     * @param mixed $query
     *
     * @return void
     */
    public function remove(Query $query)
    {
        $query = $query->getQuery();

        foreach ((array) $query->wheres as $key => $where) {
            if ($this->isSingleTableConstraint($where, $this->column)) {
                unset($query->wheres[$key]);

                $query->wheres = array_values($query->wheres);
            }
        }
    }

    /**
     * Determine if the given where clause is a single table inheritance constraint.
     *
     * @param array  $where
     * @param string $column
     *
     * @return bool
     */
    protected function isSingleTableConstraint(array $where, $column)
    {
        return $where['column'] == $column;
    }
}
