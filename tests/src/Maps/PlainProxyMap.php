<?php

namespace TestApp\Maps;

use Analogue\ORM\EntityMap;
use TestApp\PlainProxy;
use TestApp\User;

class PlainProxyMap extends EntityMap
{
    protected $attributes = null;

    protected $properties = [
        'id',
        'related',
        'related_id',
        'user',
        'user_id',
    ];

    public function related(PlainProxy $plain)
    {
        return $this->belongsTo($plain, User::class);
    }

    public function user(PlainProxy $plain)
    {
        return $this->belongsTo($plain, User::class);
    }
}
