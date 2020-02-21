<?php

namespace TestApp;

use Analogue\ORM\Entity;
use Illuminate\Support\Str;

class Article extends Entity
{
    public function setSlugAttribute($value)
    {
        $this->attributes['slug'] = Str::slug($value);
    }
}
