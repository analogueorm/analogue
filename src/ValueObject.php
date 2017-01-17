<?php

namespace Analogue\ORM;

use ArrayAccess;
use JsonSerializable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Support\Arrayable;

class ValueObject implements Mappable, ArrayAccess, Jsonable, JsonSerializable, Arrayable
{
    use MappableTrait;
    use MagicGetters;
    use MagicSetters;
    use MagicCasting;
}

