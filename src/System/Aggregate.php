<?php namespace Analogue\ORM\System;

use Illuminate\Support\Collection;
use Analogue\ORM\System\Wrappers\Factory;
use Analogue\ORM\System\Proxies\EntityProxy;
use Analogue\ORM\System\Proxies\CollectionProxy;
use Analogue\ORM\Exceptions\MappingException;

/**
 * This class is aimed to facilitate the handling of
 * complex root aggregate scenarios. 
 */
class Aggregate implements InternallyMappable {

    /**
     * The Root Entity
     * 
     * @var \Analogue\ORM\System\Wrappers\Wrapper
     */
    protected $wrappedEntity;

    /**
     * Parent Root Aggregate
     * 
     * @var \Analogue\ORM\System\Aggregate
     */
    protected $parent;

    /**
     * Parent's relationship method
     * 
     * @var string
     */
    protected $parentRelationship;

    /**
     * Root Entity
     * 
     * @var \Analogue\ORM\System\Aggregate
     */
    protected $root;

    /**
     * An associative array containing entity's 
     * relationships converted to Aggregates
     * 
     * @var array
     */
    protected $relationships = [];

    /**
     * Mapper
     *
     * @var \Analogue\ORM\System\Mapper;
     */
    protected $mapper;

    /**
     * Entity Map
     *
     * @var \Analogue\ORM\EntityMap;
     */
    protected $entityMap;

    /**
     * Create a new Aggregated Entity instance
     *
     * @param mixed          $entity             [description]
     * @param Aggregate|null $parent             [description]
     * @param string         $parentRelationship [description]
     * @param Aggregate|null $root               [description]
     */
    public function __construct($entity, Aggregate $parent = null, $parentRelationship = null, Aggregate $root = null)
    {
        $factory = new Factory;

        $this->wrappedEntity = $factory->make($entity);

        $this->parent = $parent;

        $this->parentRelationship = $parentRelationship;

        $this->root = $root;

        $this->mapper = Manager::getMapper($entity);

        $this->entityMap = $this->mapper->getEntityMap();

        $this->parseRelationships();
    }

    /**
     * Parse Every relationships defined on the entity
     * 
     * @return void
     */
    protected function parseRelationships()
    {
        foreach($this->entityMap->getRelationships() as $relation)
        {
            $this->parse($relation);
        }
    }

    /**
     * Parse one of the relationships for related entities
     * and tranform them into an array of RootAggregate objects
     * 
     * @param  string $relationship 
     * @return void
     */
    protected function parse($relationship)
    {
        // If no attribute exists for this relationships
        // we'll make it a simple empty array. This will
        // save us from constantly checking for the attributes
        // actual existence. 
        
        if(! $this->wrappedEntity->hasAttribute($relationship))
        {
            $this->relationships[$relationship] = [];
            return true;
        }

        $attribute = $this->wrappedEntity->getEntityAttribute($relationship);

        if( is_null($attribute) )
        {
            $this->relationships[$relationship] = [];
            return true;
        }

        if( $attribute instanceof EntityProxy)
        {
            $this->relationships[$relationship] = [];
            return true;
        }

        // If the relation is a proxy, we test is the relation
        // has been lazy loaded, otherwise we'll just treat
        // the subset of newly added items.
        // 
        if ($attribute instanceof CollectionProxy && $attribute->isLoaded() )
        {
            $underlying = $attribute->getUnderlyingCollection();
            $added = $attribute->getAddedItems();
            $attribute = $underlying->merge($added);
        }

        if ($attribute instanceof CollectionProxy && ! $attribute->isLoaded() )
        {
            $attribute = $attribute->getAddedItems();
        }
        if($relationship == 'permissions') tdd($attribute);
        if(is_array($attribute) || $attribute instanceof Collection)
        {
            if(! in_array($relationship, $this->entityMap->getManyRelationships() ))
            {
                throw new MappingException("Expecting object in '$relationship', found array() or Collection");
            }

            $this->relationships[$relationship] = $this->createSubAggregates($attribute, $relationship);

            return true;
        }

        
        //$attributeWrapper = $this->factory->make($attribute);

        $attributeClass = get_class($attribute);

        if($this->parent != null && $attributeClass == $this->parent->getEntityClass() ) return true;
        if($this->root != null && $attributeClass == $this->root->getEntityClass() ) return true;


        // At this point, we can assume the attribute is an Entity instance
        // so we'll treat it as such. Note that we'll store it as an array
        // just for consistency with other relationships 
        $subAggregate = $this->createSubAggregate($attribute, $relationship);
        
        // If the related entity is the same Hash as the Parent or the Root
        // we'll skip them, to avoid looping through ancestors. 
        /*$hash = $subAggregate->getEntityHash();

        if($this->parent != null && $this->parent->getEntityHash() == $hash) return true;
        if($this->root != null && $this->root->getEntityHash() == $hash) return true;*/
        

        $this->relationships[$relationship] = [$subAggregate];

        return true;
    }

    /**
     * Create a child, aggregated entity
     * 
     * @param  array|collection $entities
     * @return 
     */
    protected function createSubAggregates($entities, $relation)
    {
        $aggregates = [];

        foreach($entities as $entity)
        {
            $aggregates[] = $this->createSubAggregate($entity, $relation);
        }

        return $aggregates;
    }

    /**
     * Create a related subAggregate
     * 
     * @param  mixed $entity 
     * @return \Analogue\ORM\System\RootAggregate;
     */
    protected function createSubAggregate($entity, $relation)
    {   
        // If root isn't defined, then this is the Aggregate Root
        if(is_null($this->root))
        {
            $root = $this;
        }
        else $root = $this->root;

        $aggregate = new Aggregate($entity, $this, $relation, $root);

        return $aggregate;
    }

    /**
     * Get the Entity's primary key attribute
     * 
     * @return string|integer
     */
    public function getEntityId()
    {
        return $this->wrappedEntity->getEntityAttribute($this->entityMap->getKeyName() );
    }

    /**
     * Get the name of the primary key
     * 
     * @return string
     */
    public function getEntityKey()
    {
        return $this->entityMap->getKeyName();
    }

    /**
     * Return the entity map for the current entity
     * 
     * @return \Analogue\ORM\EntityMap
     */
    public function getEntityMap()
    {
        return $this->entityMap;
    }

    /**
     * Return the Entity's hash $class.$id
     *
     * @return string
     */
    public function getEntityHash()
    {
        return $this->getEntityClass().'.'.$this->getEntityId();
    }

    /**
     * Get wrapped entity class
     * 
     * @return 
     */
    public function getEntityClass()
    {
        return $this->entityMap->getClass();
    }

    /**
     * Return the Mapper's entity cache
     *
     * @return \Analogue\ORM\System\EntityCache
     */
    protected function getEntityCache()
    {
        return $this->mapper->getEntityCache();
    }

    /**
     * Get a relationship as an aggregated entities' array
     * 
     * @param  string $name 
     * @return array
     */
    public function getRelationship($name)
    {
        if(array_key_exists($name, $this->relationships))
        {
            return $this->relationships[$name];
        }
        else return [];
    }

    /**
     * [TO IMPLEMENT]
     * 
     * @return array
     */
    public function getPivotAttributes()
    {
        return [];
    }

    /**
     * Get Non existing related entities from several relationships
     * 
     * @param  array  $relationships 
     * @return array
     */
    public function getNonExistingRelated(array $relationships)
    {
        $nonExisting = [];

        foreach($relationships as $relation)
        {
            if($this->hasAttribute($relation) && array_key_exists($relation, $this->relationships))
            {
                $nonExisting = array_merge($nonExisting,$this->getNonExistingFromRelation($relation));
            }
        }

        return $nonExisting;
    }

    /**
     * Get non-existing related entities from a single relation
     * 
     * @param  string $relation
     * @return array
     */
    protected function getNonExistingFromRelation($relation)
    {
        $nonExisting = [];

        foreach($this->relationships[$relation] as $aggregate)
        {
            if (! $aggregate->exists() )
            {
                $nonExisting[] = $aggregate;
            }
        }   

        return $nonExisting;
    }

    /**
     * Returns an array of Missing related Entities for the 
     * given $relation
     * 
     * @param  string $relation
     * @return array
     */
    public function getMissingEntities($relation)
    {
        $cachedRelations = $this->getCachedAttribute($relation);

        if (! is_null($cachedRelations))
        {
            $missing = [];

            foreach($cachedRelations as $hash)
            {
                if (! $this->getRelatedAggregateFromHash($hash, $relation))
                {
                    $missing[] = $hash;
                }
            }

            return $missing;
        }
        else return [];
    }

    /**
     * Check in the cache for missing relationship
     * 
     * @return array
     */
    public function detachMissingRelationships()
    {
        foreach($this->entityMap->getForeignRelationships() as $relation)
        {
            $missingRelationships = $this->getMissingEntities($relation);

            if(count($missingRelationships) > 0)
            {
                 $this->entityMap->$relation($this->getEntityObject() )->detachMany($missingRelationships);
            }
        }
    }
       
    /**
     * Get Relationships who have dirty attributes / dirty relationships
     * 
     * @return array
     */
    public function getDirtyRelationships()
    {
        $dirtyAggregates = [];

        foreach($this->relationships as $relation)
        {
            foreach($relation as $aggregate)
            {
                if($aggregate->isDirty() || $aggregate->hasDirtyRelationships())
                {
                    $dirtyAggregates[] = $aggregate;
                }
            }
        }

        return $dirtyAggregates; 
    }
    
    /**
     * Compare the object's raw attributes with the record in cache
     * 
     * @return boolean 
     */
    public function isDirty()
    {
        if (count($this->getDirtyRawAttributes()) > 0)
        {
            return true;
        }
        else return false;
    }

    /**
     * Check if any of the related entities are dirty
     * 
     * @return boolean 
     */
    public function hasDirtyRelationships()
    {

    }

    /**
     * Get Raw Entity's attributes, as they are represented
     * in the database, including value objects & foreign keys
     * 
     * @return array
     */
    public function getRawAttributes()
    {
        $attributes = $this->wrappedEntity->getEntityAttributes();

        foreach($this->entityMap->getRelationships() as $relation)
        {
            unset($attributes[$relation]);
        }

        $attributes = $this->flattenEmbeddables($attributes);

        $foreignKeys = $this->getForeignKeyAttributes();

        return $attributes + $foreignKeys;
    }

    /**
     * Convert Value Objects to raw db attributes
     * 
     * @param  array $attributes 
     * @return array
     */
    protected function flattenEmbeddables($attributes)
    {
        $embeddables = $this->entityMap->getEmbeddables();
        
        foreach($embeddables as $localKey => $embed)
        {
            // Retrieve the value object from the entity's attributes
            $valueObject = $attributes[$localKey];

            // Unset the corresponding key
            unset($attributes[$localKey]);

            // TODO Make wrapper object compatible with value objects
            $valueObjectAttributes = $valueObject->getEntityAttributes();

            // Now (if setup in the entity map) we prefix the value object's
            // attributes with the snake_case name of the embedded class.
            $prefix = snake_case(class_basename($embed));

            foreach($valueObjectAttributes as $key=>$value)
            {
                $valueObjectAttributes[$prefix.'_'.$key] = $value;
                unset($valueObjectAttributes[$key]);
            }

            $attributes = array_merge($attributes, $valueObjectAttributes);
        }
        
        return $attributes;
    }

    /**
     * Return's entity raw attributes in the state they were at last
     * query. 
     * 
     * @return array
     */
    protected function getCachedRawAttributes(array $columns = null)
    {
        $cachedAttributes = $this->getCache()->get($this->getEntityId());

        if(is_null($columns) )
        {   
            return $cachedAttributes;
        }   
        else
        {
            return array_only($cachedAttributes, $columns);
        }
    }

    /**
     * Return a single attribute from the cache
     * @param  string $key 
     * @return mixed      
     */
    protected function getCachedAttribute($key)
    {
        $cachedAttributes = $this->getCache()->get($this->getEntityId());

        if(! array_key_exists($key, $cachedAttributes))
        {
            return null;
        }
        else return $cachedAttributes[$key];
    }

    /**
     * Convert related Entity's attributes to foreign keys
     * 
     * @return array
     */
    protected function getForeignKeyAttributes()
    {
        $foreignKeys = [];

        foreach($this->entityMap->getLocalRelationships() as $relation)
        {
            // check if relationship has been parsed, meaning it has an actual object 
            // in the entity's attributes
            if ($this->isActualRelationships($relation))
            {
                $foreignKeys = $foreignKeys + $this->getForeignKeyAttributesFromRelation($relation);
            }
        }

        if(! is_null ($this->parent))
        {
            $foreignKeys = $foreignKeys + $this->getForeignKeyAttributesFromParent();
        }

        return $foreignKeys;
    }

    /**
     * Return an associative array containing the key-value pair(s) from
     * the related entity.
     * 
     * @param  string $relation 
     * @return array
     */
    protected function getForeignKeyAttributesFromRelation($relation)
    {
        $localRelations = $this->entityMap->getLocalRelationships();

        if(in_array($relation, $localRelations))
        {
            // Call Relationship's method 
            $relationship = $this->entityMap->$relation($this->getEntityObject() );

            $relatedAggregate = $this->relationships[$relation][0];

            return $relationship->getForeignKeyValuePair($relatedAggregate->getEntityObject() );
        }
        else return [];
    }

    /**
     * Get foreign key attribute(s) from a parent entity in this
     * aggregate context
     * 
     * @param  string $relation 
     * @return array
     */
    protected function getForeignKeyAttributesFromParent()
    {
        $parentMap = $this->parent->getEntityMap();

        $parentForeignRelations = $parentMap->getForeignRelationships();
        $parentPivotRelations = $parentMap->getPivotRelationships();

        $parentRelation = $this->parentRelationship;

        if (in_array($parentRelation, $parentForeignRelations)
            && ! in_array($parentRelation, $parentPivotRelations))
        {
            $parentObject = $this->parent->getEntityObject();

            // Call Relationship's method on parent map
            $relationship = $parentMap->$parentRelation($parentObject);

            return $relationship->getForeignKeyValuePair();
        }
        else
        {
            return [];
        }
    }

    /**
     * Update Pivot records on loaded relationships, by comparing the
     * values from the Entity Cache to the actual relationship inside
     * the aggregated entity.
     * 
     * @return void
     */
    public function updatePivotRecords()
    {
        $pivots = $this->entityMap->getPivotRelationships();

        foreach($pivots as $pivot)
        {
            if(array_key_exists($pivot, $this->relationships)) $this->updatePivotRelation($pivot);
        }
    }

    /**
     * Update Single pivot relationship
     * 
     * @param  string $relation 
     * @return void
     */
    protected function updatePivotRelation($relation)
    {   
        $hashes = $this->getEntityHashesFromRelation($relation);

        $cachedAttributes = $this->getCachedRawAttributes();

        if (array_key_exists($relation, $cachedAttributes))
        {
            // Compare the two array of hashes to find out existing
            // pivot records, and the ones to be created.
            //tdd($cachedAttributes);
            $new = array_diff($hashes, array_keys($cachedAttributes[$relation]));
            $existing = array_intersect($hashes, array_keys($cachedAttributes[$relation])); 
        }
        else
        {
            $existing = [];
            $new = $hashes;
        }

        if(count($new) > 0)
        {
            $relatedCollection = $this->getEntityAttribute($relation);

            $pivots = $this->getRelatedAggregatesFromHashes($new, $relation);

            $this->entityMap->$relation($this->getEntityObject() )->createPivots($pivots);
        }

        if(count($existing) > 0)
        {
            foreach($existing as $pivotHash)
            {
                $this->updatePivotIfDirty($pivotHash, $relation);
            }
        }
    }

    /**
     * Compare existing pivot record in cache and update it 
     * if the pivot attributes are dirty
     * 
     * @param  string $pivotHash 
     * @param  string $relation  
     * @return void
     */
    protected function updatePivotIfDirty($pivotHash, $relation)
    {
        $aggregate = $this->getRelatedAggregateFromHash($pivotHash, $relation);

        if($aggregate->hasAttribute('pivot'))
        {
            $pivotAttribute = $aggregate->getEntityAttribute('pivot');
            $cachedPivot = $this->getPivotAttributesFromCache($pivotHash, $relation);
        }
    }

    /**
     * 
     * @param  string $pivotHash 
     * @param  string $relation  
     * @return array
     */
    protected function getPivotAttributesFromCache($pivotHash, $relation)
    {
        $cachedAttributes = $this->getCachedRawAttributes();

        $cachedRelations = $cachedAttributes[$relation];

        foreach($cachedRelations as $cachedRelation)
        {
            if($cachedRelation == $pivotHash)
            {
                return $cachedRelation->getPivotAttributes();
            }
        }

    }

    /**
     * Returns an array of related Aggregates from its entity hashes
     * 
     * @param  array  $hashes   
     * @param  string $relation
     * @return array
     */
    protected function getRelatedAggregatesFromHashes(array $hashes, $relation)
    {
        $related = [];

        foreach($hashes as $hash)
        {
            $aggregate = $this->getRelatedAggregateFromHash($hash, $relation);

            if(! is_null($aggregate)) $related[] = $aggregate;
        }

        return $related;
    }

    /**
     * Get related aggregate from its hash
     * 
     * @param  string $hash
     * @param  string $relation
     * @return \Analogue\ORM\System\Aggregate | null
     */
    protected function getRelatedAggregateFromHash($hash, $relation)
    {
        foreach($this->relationships[$relation] as $aggregate)
        {
            if ($aggregate->getEntityHash() == $hash)
            {
                return $aggregate;
            }
        }
        return null;
    }

    /**
     * Return an array of Entity Hashes from a specific relation
     * 
     * @param  string $relation 
     * @return array
     */
    protected function getEntityHashesFromRelation($relation)
    {
        return array_map(function($aggregate) {
            return $aggregate->getEntityHash();
        }, $this->relationships[$relation]);
    }

    /**
     * Check the existence of an actual relationship
     *
     * @param  string $relation
     * @return boolean
     */
    protected function isActualRelationships($relation)
    {
        return array_key_exists($relation, $this->relationships)
            && count($this->relationships[$relation]) > 0;
    }

    /**
     * Return cache instance for the current entity type
     * 
     * @return \Analogue\ORM\System\EntityCache
     */
    protected function getCache()
    {
        return $this->mapper->getEntityCache();
    }

    /**
     * Get Only Raw Entiy's attributes which have been modified
     * since last query
     * 
     * @return array
     */
    public function getDirtyRawAttributes()
    {
        $attributes = $this->getRawAttributes();
        $cachedAttributes = $this->getCachedRawAttributes(array_keys($attributes));

        $dirty = [];

        foreach($attributes as $key => $value)
        {
            if ($this->isRelation($key) || $key == 'pivot') continue;

            if ( ! array_key_exists($key, $cachedAttributes) && ! $value instanceof Pivot)
            {
                $dirty[$key] = $value;
            }
            elseif ($value !== $cachedAttributes[$key] && 
                ! $this->originalIsNumericallyEquivalent($value, $cachedAttributes[$key]))
            {
                $dirty[$key] = $value;
            }
        }

        return $dirty;
    }

    protected function isRelation($key)
    {
        return in_array($key, $this->entityMap->getRelationships() );
    }

    /**
     * Determine if the new and old values for a given key are numerically equivalent.
     *
     * @return boolean
     */
    protected function originalIsNumericallyEquivalent($current, $original)
    {
        return is_numeric($current) && is_numeric($original) && strcmp((string) $current, (string) $original) === 0;
    }

    /**
     * Get the underlying entity object
     * 
     * @return mixed
     */
    public function getEntityObject()
    {
        return $this->wrappedEntity->getObject();
    }

    /**
     * Return the Mapper instance for the current Entity Type
     *
     * @return \Analogue\ORM\System\Mapper
     */
    public function getMapper()
    {
        return $this->mapper;
    }

    /**
     * Does the entity already exists in the database ?
     * 
     * @return boolean
     */
    public function exists()
    {
        return $this->getCache()->has($this->getEntityId() );
    }

    /**
     * Set the object attribute raw values (hydration)
     * 
     * @param array $attributes 
     */
    public function setEntityAttributes(array $attributes)
    {
        $this->wrappedEntity->setEntityAttributes($attributes);
    }

    /**
     * Get the raw object's values.
     * 
     * @return array
     */
    public function getEntityAttributes()
    {
        return $this->wrappedEntity->getEntityAttributes();
    }

    /**
     * Set the raw entity attributes
     * @param string $key  
     * @param string $value
     */
    public function setEntityAttribute($key, $value)
    {
        $this->wrappedEntity->setEntityAttribute($key, $value);
    }

    /**
     * Return the entity's attribute 
     * @param  string $key 
     * @return mixed
     */
    public function getEntityAttribute($key)
    {
        return $this->wrappedEntity->getEntityAttribute($key);
    }

    /**
     * Does the attribute exists on the entity
     * 
     * @param  string  $key
     * @return boolean     
     */
    public function hasAttribute($key)
    {
        return $this->wrappedEntity->hasAttribute($key);
    }

    /**
     * Set the lazyloading proxies on the wrapped entity
     *
     * @return void
     */
    public function setProxies()
    {
        return $this->wrappedEntity->setProxies();
    }

}
