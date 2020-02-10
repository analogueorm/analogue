<?php

namespace Analogue\ORM\System\Builders;

use Analogue\ORM\System\Mapper;

class ResultBuilderFactory
{
    public function make(Mapper $mapper, bool $skipCache = false): ResultBuilderInterface
    {
        switch ($mapper->getEntityMap()->getInheritanceType()) {
            case 'single_table':
                return new PolymorphicResultBuilder($mapper);
            default:
                return new ResultBuilder($mapper, !$skipCache);
        }
    }
}
