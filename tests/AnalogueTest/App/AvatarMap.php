<?php namespace AnalogueTest\App;

use Analogue\ORM\EntityMap;

class AvatarMap extends EntityMap {

    protected $table='custom_avatar';

    public function image(Avatar $avatar)
    {
        return $this->morphOne($avatar, 'AnalogueTest\App\Image', 'imageable');
    }

    public function user(Avatar $avatar)
    {
        return $this->belongsTo($avatar, 'AnalogueTest\App\User');
    }
}
