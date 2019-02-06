<?php

namespace Analogue\ORM;

use Illuminate\Pagination\LengthAwarePaginator;

class LengthAwareEntityPaginator extends LengthAwarePaginator
{
    /**
     * Paginator constructor.
     *
     * @param mixed $items
     * @param int   $total
     * @param int   $perPage
     * @param null  $currentPage
     * @param array $options
     */
    public function __construct($items, $total, $perPage, $currentPage = null, array $options = [])
    {
        $items = $items instanceof EntityCollection ? $items : EntityCollection::make($items);

        parent::__construct($items, $total, $perPage, $currentPage, $options);
    }
}
