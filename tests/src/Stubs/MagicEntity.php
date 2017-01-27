<?php

namespace TestApp\Stubs;

use Analogue\ORM\MagicCasting;
use Analogue\ORM\MagicGetters;
use Analogue\ORM\MagicSetters;

class MagicEntity
{
    use MagicGetters;
    use MagicSetters;
    use MagicCasting;

    protected $classProperty = 'Some Value';

    public function __construct()
    {
        $this->attributes['attr1'] = 'Some Value';
        $this->attributes['attr2'] = 'Some Value';
    }
}
