<?php

namespace TestApp;

use Analogue\ORM\Entity;

class Article extends Entity
{
    public function setSlugAttribute($value)
    {
        $this->attributes['slug'] = str_slug($value);
    }
}
