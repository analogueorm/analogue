<?php

namespace Analogue\ORM;

use ArrayAccess;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use JsonSerializable;

class ValueObject implements Mappable, ArrayAccess, Jsonable, JsonSerializable, Arrayable
{
    use MappableTrait;
    use MagicGetters;
    use MagicSetters;
    use MagicCasting;
}
