<?php namespace AnalogueTest\App;

use Analogue\ORM\EntityMap;

class ResourceMap extends EntityMap {

    public $timestamps = true;

    public $softDeletes = true;

    protected $primaryKey = 'custom_id';

    protected $createdAtColumn = 'custom_created_at';

    protected $updatedAtColumn = 'custom_updated_at';

    protected $deletedAtColumn = 'custom_deleted_at';    

    protected $embeddables = ['value' => 'AnalogueTest\App\V'];

    public function roles(Resource $resource)
    {
        return $this->morphedByMany($resource, 'AnalogueTest\App\Role', 'resource_owner');
    }

    public function users(Resource $resource)
    {
        return $this->morphedByMany($resource, 'AnalogueTest\App\User', 'resource_owner');
    }

    public function images(Resource $resource)
    {
        return $this->morphMany($resource, 'AnalogueTest\App\Image', 'imageable');
    }
}
