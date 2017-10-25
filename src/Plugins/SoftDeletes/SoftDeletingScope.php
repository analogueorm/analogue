<?php

namespace Analogue\ORM\Plugins\SoftDeletes;

use Analogue\ORM\System\Query;
use Analogue\ORM\System\ScopeInterface;

class SoftDeletingScope implements ScopeInterface
{
    /**
     * All of the extensions to be added to the builder.
     *
     * @var array
     */
    protected $extensions = [
        'WithTrashed',
        'OnlyTrashed',
    ];

    /**
     * {@inheritdoc}
     */
    public function apply(Query $query)
    {
        $entityMap = $query->getMapper()->getEntityMap();

        $query->whereNull($entityMap->getQualifiedDeletedAtColumn());

        $this->extend($query);
    }

    /**
     * {@inheritdoc}
     */
    public function remove(Query $query)
    {
        $column = $query->getMapper()->getEntityMap()->getQualifiedDeletedAtColumn();

        $query = $query->getQuery();

        foreach ((array) $query->wheres as $key => $where) {
            // If the where clause is a soft delete date constraint, we will remove it from
            // the query and reset the keys on the wheres. This allows this developer to
            // include deleted model in a relationship result set that is lazy loaded.
            if ($this->isSoftDeleteConstraint($where, $column)) {
                unset($query->wheres[$key]);

                $query->wheres = array_values($query->wheres);
            }
        }
    }

    /**
     * Extend the query builder with the needed functions.
     *
     * @param \Analogue\ORM\System\Query $query
     *
     * @return void
     */
    public function extend(Query $query)
    {
        foreach ($this->extensions as $extension) {
            $this->{"add{$extension}"}($query);
        }
    }

    /**
     * Add the with-trashed extension to the builder.
     *
     * @param \Analogue\ORM\System\Query $query
     *
     * @return void
     */
    protected function addWithTrashed(Query $query)
    {
        $query->macro('withTrashed', function (Query $query) {
            $this->remove($query);

            return $query;
        });
    }

    /**
     * Add the only-trashed extension to the builder.
     *
     * @param \Analogue\ORM\System\Query $query
     *
     * @return void
     */
    protected function addOnlyTrashed(Query $query)
    {
        $query->macro('onlyTrashed', function (Query $query) {
            $this->remove($query);

            $query->getQuery()->whereNotNull($query->getMapper()->getEntityMap()->getQualifiedDeletedAtColumn());

            return $query;
        });
    }

    /**
     * Determine if the given where clause is a soft delete constraint.
     *
     * @param array  $where
     * @param string $column
     *
     * @return bool
     */
    protected function isSoftDeleteConstraint(array $where, $column)
    {
        return $where['type'] == 'Null' && $where['column'] == $column;
    }
}
