<?php

namespace Analogue\ORM\System\Builders;

interface ResultBuilderInterface
{
    public function build(array $results, array $eagerLoads);
}
