<?php

namespace Analogue\ORM;

use ArrayAccess;
use Carbon\Carbon;
use JsonSerializable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Support\Arrayable;
use Analogue\ORM\System\Proxies\ProxyInterface;

class ValueObject implements Mappable, ArrayAccess, Jsonable, JsonSerializable, Arrayable
{
    use MappableTrait;
    use MagicGetters;
    use MagicSetters;
    use MagicCasting;
}

