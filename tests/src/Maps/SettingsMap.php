<?php

namespace TestApp\Maps;

use Analogue\ORM\EntityMap;
use TestApp\Option;
use TestApp\Settings;

class SettingsMap extends EntityMap
{
    public function options(Settings $settings)
    {
        return $this->embedsMany($settings, Option::class)->asJson();
    }
}
