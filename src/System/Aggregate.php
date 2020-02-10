<?php

namespace Analogue\ORM\System;

use Analogue\ORM\EntityMap;
use Analogue\ORM\Exceptions\MappingException;
use Analogue\ORM\Relationships\Pivot;
use Analogue\ORM\System\Cache\AttributeCache;
use Analogue\ORM\System\Proxies\CollectionProxy;
use Analogue\ORM\System\Wrappers\Factory;
use Illuminate\Support\Collection;
use MongoDB\BSON\ObjectId;
use ProxyManager\Proxy\LazyLoadingInterface;
use ProxyManager\Proxy\ProxyInterface;

/**
 * This class is aimed to facilitate the handling of
 * complex root aggregate scenarios.
 */
class Aggregate implements InternallyMappable
{
    /**
     * The Root Entity.
     *
     * @var \Analogue\ORM\System\Wrappers\Wrapper
     */
    protected $wrappedEntity;

    /**
     * Class of the entity being aggregated.
     *
     * @var string
     */
    protected $class;

    /**
     * Parent Root Aggregate.
     *
     * @var \Analogue\ORM\System\Aggregate
     */
    protected $parent;

    /**
     * Parent's relationship method.
     *
     * @var string
     */
    protected $parentRelationship;

    /**
     * Root Entity.
     *
     * @var \Analogue\ORM\System\Aggregate
     */
    protected $root;

    /**
     * An associative array containing entity's
     * relationships converted to Aggregates.
     *
     * @var array
     */
    protected $relationships = [];

    /**
     * Relationship that need post-command synchronization.
     *
     * @var array
     */
    protected $needSync = [];

    /**
     * Mapper.
     *
     * @var \Analogue\ORM\System\Mapper;
     */
    protected $mapper;

    /**
     * Entity Map.
     *
     * @var \Analogue\ORM\EntityMap;
     */
    protected $entityMap;

    /**
     * Create a new Aggregated Entity instance.
     *
     * @param mixed          $entity
     * @param Aggregate|null $parent
     * @param string         $parentRelationship
     * @param Aggregate|null $root
     *
     * @throws MappingException
     */
    public function __construct($entity, self $parent = null, string $parentRelationship = null, self $root = null)
    {
        $factory = new Factory();

        $this->class = get_class($entity);

        $this->wrappedEntity = $factory->make($entity);

        $this->parent = $parent;

        $this->parentRelationship = $parentRelationship;

        $this->root = $root;

        $mapper = $this->getMapper();

        $this->entityMap = $mapper->getEntityMap();

        $this->parseRelationships();
    }

    /**
     * Parse Every relationships defined on the entity.
     *
     * @throws MappingException
     *
     * @return void
     */
    protected function parseRelationships()
    {
        foreach ($this->entityMap->getSingleRelationships() as $relation) {
            $this->parseSingleRelationship($relation);
        }

        foreach ($this->entityMap->getManyRelationships() as $relation) {
            $this->parseManyRelationship($relation);
        }
    }

    /**
     * Parse for values common to single & many relations.
     *
     * @param string $relation
     *
     * @throws MappingException
     *
     * @return mixed|bool
     */
    protected function parseForCommonValues($relation)
    {
        if (!$this->hasAttribute($relation)) {
            // If no attribute exists for this relationships
            // we'll make it a simple empty array. This will
            // save us from constantly checking for the attributes
            // actual existence.
            $this->relationships[$relation] = [];

            return false;
        }

        $value = $this->getRelationshipValue($relation);

        if (is_null($value)) {
            $this->relationships[$relation] = [];

            // If the relationship's content is the null value
            // and the Entity's exist in DB, we'll interpret this
            // as the need to detach all related Entities,
            // therefore a sync operation is needed.
            $this->needSync[] = $relation;

            return false;
        }

        return $value;
    }

    /**
     * Parse a 'single' relationship.
     *
     * @param string $relation
     *
     * @throws MappingException
     *
     * @return bool
     */
    protected function parseSingleRelationship(string $relation): bool
    {
        if (!$value = $this->parseForCommonValues($relation)) {
            return true;
        }

        if ($value instanceof Collection || is_array($value) || $value instanceof CollectionProxy) {
            throw new MappingException("Entity's attribute $relation should not be array, or collection");
        }

        if ($value instanceof LazyLoadingInterface && !$value->isProxyInitialized()) {
            $this->relationships[$relation] = [];

            return true;
        }

        // If the attribute is a loaded proxy, swap it for its
        // loaded entity.
        if ($value instanceof LazyLoadingInterface && $value->isProxyInitialized()) {
            $value = $value->getWrappedValueHolderValue();
        }

        if ($this->isParentOrRoot($value)) {
            $this->relationships[$relation] = [];

            return true;
        }

        // At this point, we can assume the attribute is an Entity instance
        // so we'll treat it as such.
        $subAggregate = $this->createSubAggregate($value, $relation);

        // Even if it's a single entity, we'll store it as an array
        // just for consistency with other relationships
        $this->relationships[$relation] = [$subAggregate];

        // We always need to check a loaded relation is in sync
        // with its local key
        $this->needSync[] = $relation;

        return true;
    }

    /**
     * Check if value isn't parent or root in the aggregate.
     *
     * @param  mixed
     *
     * @return bool
     */
    protected function isParentOrRoot($value): bool
    {
        $id = spl_object_hash($value);
        $root = $this->root ? $this->root->getWrappedEntity()->getObject() : null;
        $parent = $this->parent ? $this->parent->getWrappedEntity()->getObject() : null;

        if ($parent && (spl_object_hash($parent) == $id)) {
            return true;
        }

        if ($root && (spl_object_hash($root) == $id)) {
            return true;
        }

        return false;
    }

    /**
     * Parse a 'many' relationship.
     *
     * @param string $relation
     *
     * @throws MappingException
     *
     * @return bool
     */
    protected function parseManyRelationship(string $relation): bool
    {
        if (!$value = $this->parseForCommonValues($relation)) {
            return true;
        }

        if (is_array($value) || (!$value instanceof CollectionProxy && $value instanceof Collection)) {
            $this->needSync[] = $relation;
        }

        // If the relation is a proxy, we test is the relation
        // has been lazy loaded, otherwise we'll just treat
        // the subset of newly added items.
        if ($value instanceof CollectionProxy && $value->isProxyInitialized()) {
            $this->needSync[] = $relation;
            //$value = $value->getUnderlyingCollection();
        }

        if ($value instanceof CollectionProxy && !$value->isProxyInitialized()) {
            $value = $value->getAddedItems();
        }

        // At this point $value should be either an array or an instance
        // of a collection class.
        if (!is_array($value) && !$value instanceof Collection) {
            throw new MappingException("'$relation' attribute should be array() or Collection");
        }

        $this->relationships[$relation] = $this->createSubAggregates($value, $relation);

        return true;
    }

    /**
     * Return Entity's relationship attribute.
     *
     * @param string $relation
     *
     * @throws MappingException
     *
     * @return mixed
     */
    protected function getRelationshipValue(string $relation)
    {
        $value = $this->getEntityAttribute($relation);

        if (is_scalar($value)) {
            throw new MappingException("Entity's attribute $relation should be array, object, collection or null");
        }

        return $value;
    }

    /**
     * Create a child, aggregated entity.
     *
     * @param mixed  $entities
     * @param string $relation
     *
     * @return array
     */
    protected function createSubAggregates($entities, string $relation): array
    {
        $aggregates = [];

        foreach ($entities as $entity) {
            $aggregates[] = $this->createSubAggregate($entity, $relation);
        }

        return $aggregates;
    }

    /**
     * Create a related subAggregate.
     *
     * @param mixed  $entity
     * @param string $relation
     *
     * @throws MappingException
     *
     * @return Aggregate
     */
    protected function createSubAggregate($entity, string $relation): self
    {
        // If root isn't defined, then this is the Aggregate Root
        $root = is_null($this->root) ? $this : $this->root;

        return new self($entity, $this, $relation, $root);
    }

    /**
     * Return the entity map for the current entity.
     *
     * @return \Analogue\ORM\EntityMap
     */
    public function getEntityMap(): EntityMap
    {
        return $this->entityMap;
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityHash(): string
    {
        return $this->getEntityClass().'.'.$this->getEntityKeyValue();
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityKeyName(): string
    {
        return $this->entityMap->getKeyName();
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityKeyValue()
    {
        $keyValue = $this->wrappedEntity->getEntityKeyValue();

        if ($keyValue instanceof ObjectId) {
            $keyValue = (string) $keyValue;
        }

        return $keyValue;
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityClass(): string
    {
        return $this->entityMap->getClass();
    }

    /**
     * Return the Mapper's entity cache.
     *
     * @return \Analogue\ORM\System\Cache\AttributeCache
     */
    protected function getEntityCache(): AttributeCache
    {
        return $this->getMapper()->getEntityCache();
    }

    /**
     * Get a relationship as an aggregated entities' array.
     *
     * @param string $name
     *
     * @return array
     */
    public function getRelationship(string $name): array
    {
        if (array_key_exists($name, $this->relationships)) {
            return $this->relationships[$name];
        }

        return [];
    }

    /**
     * [TO IMPLEMENT].
     *
     * @return array
     */
    public function getPivotAttributes(): array
    {
        return [];
    }

    /**
     * Get Non existing related entities from several relationships.
     *
     * @param array $relationships
     *
     * @return array
     */
    public function getNonExistingRelated(array $relationships): array
    {
        $nonExisting = [];

        foreach ($relationships as $relation) {
            if ($this->hasAttribute($relation) && array_key_exists($relation, $this->relationships)) {
                $nonExisting = array_merge($nonExisting, $this->getNonExistingFromRelation($relation));
            }
        }

        return $nonExisting;
    }

    /**
     * Get non-existing related entities from a single relation.
     *
     * @param string $relation
     *
     * @return array
     */
    protected function getNonExistingFromRelation(string $relation): array
    {
        $nonExisting = [];

        foreach ($this->relationships[$relation] as $aggregate) {
            if (!$aggregate->exists()) {
                $nonExisting[] = $aggregate;
            }
        }

        return $nonExisting;
    }

    /**
     * Synchronize relationships if needed.
     *
     * @param array
     *
     * @return void
     */
    public function syncRelationships(array $relationships)
    {
        foreach ($relationships as $relation) {
            if (in_array($relation, $this->needSync)) {
                $this->synchronize($relation);
            }
        }
    }

    /**
     * Synchronize a relationship attribute.
     *
     * @param string $relation
     *
     * @return void
     */
    protected function synchronize(string $relation)
    {
        $actualContent = $this->relationships[$relation];

        $relationshipObject = $this->entityMap->$relation($this->getEntityObject());
        $relationshipObject->setParent($this->wrappedEntity);
        $relationshipObject->sync($actualContent);
    }

    /**
     * Returns an array of Missing related Entities for the
     * given $relation.
     *
     * @param string $relation
     *
     * @return array
     */
    public function getMissingEntities(string $relation): array
    {
        $cachedRelations = $this->getCachedAttribute($relation);

        if (is_null($cachedRelations)) {
            return [];
        }

        $missing = [];

        foreach ($cachedRelations as $hash) {
            if (!$this->getRelatedAggregateFromHash($hash, $relation)) {
                $missing[] = $hash;
            }
        }

        return $missing;
    }

    /**
     * Get Relationships who have dirty attributes / dirty relationships.
     *
     * @return array
     */
    public function getDirtyRelationships(): array
    {
        $dirtyAggregates = [];

        foreach ($this->relationships as $relation) {
            foreach ($relation as $aggregate) {
                if (!$aggregate->exists() || $aggregate->isDirty() || count($aggregate->getDirtyRelationships()) > 0) {
                    $dirtyAggregates[] = $aggregate;
                }
            }
        }

        return $dirtyAggregates;
    }

    /**
     * Compare the object's raw attributes with the record in cache.
     *
     * @return bool
     */
    public function isDirty(): bool
    {
        return count($this->getDirtyRawAttributes()) > 0;
    }

    /**
     * Get Raw Entity's attributes, as they are represented
     * in the database, including value objects, foreign keys,
     * and discriminator column.
     *
     * @return array
     */
    public function getRawAttributes(): array
    {
        $attributes = $this->wrappedEntity->getEntityAttributes();

        foreach ($this->entityMap->getNonEmbeddedRelationships() as $relation) {
            unset($attributes[$relation]);
        }

        if ($this->entityMap->getInheritanceType() == 'single_table') {
            $attributes = $this->addDiscriminatorColumn($attributes);
        }

        $attributes = $this->entityMap->getColumnNamesFromAttributes($attributes);

        $attributes = $this->flattenEmbeddables($attributes);

        $foreignKeys = $this->getForeignKeyAttributes();

        return $this->mergeForeignKeysWithAttributes($foreignKeys, $attributes);
    }

    /**
     * Merge foreign keys and attributes by comparing their
     * current value to the cache, and guess the user intent.
     *
     * @param array $foreignKeys
     * @param array $attributes
     *
     * @return array
     */
    protected function mergeForeignKeysWithAttributes(array $foreignKeys, array $attributes): array
    {
        $cachedAttributes = $this->getCachedRawAttributes();

        foreach ($foreignKeys as $fkAttributeKey => $fkAttributeValue) {
            // FK doesn't exist in attributes => we set it
            if (!array_key_exists($fkAttributeKey, $attributes)) {
                $attributes[$fkAttributeKey] = $fkAttributeValue;
                continue;
            }

            // FK does exists in attributes and is equal => we set it
            if ($attributes[$fkAttributeKey] === $fkAttributeValue) {
                $attributes[$fkAttributeKey] = $fkAttributeValue;
                continue;
            }

            // ForeignKey exists in attributes array, but the value is different that
            // the one fetched from the relationship itself.

            // Does it exist in cache
            if (array_key_exists($fkAttributeKey, $cachedAttributes)) {
                // attribute is different than cached value, we use it
                if ($attributes[$fkAttributeKey] !== $cachedAttributes[$fkAttributeKey]) {
                    continue;
                } else { // if not, we use the foreign key value
                    $attributes[$fkAttributeKey] = $fkAttributeValue;
                }
            } else {
                if (is_null($attributes[$fkAttributeKey])) {
                    $attributes[$fkAttributeKey] = $fkAttributeValue;
                }
            }
        }

        return $attributes;
    }

    /**
     * Add Discriminator Column if it doesn't exist on the actual entity.
     *
     * @param array $attributes
     *
     * @return array
     */
    protected function addDiscriminatorColumn(array $attributes): array
    {
        $discriminatorColumn = $this->entityMap->getDiscriminatorColumn();
        $entityClass = $this->entityMap->getClass();

        if (!array_key_exists($discriminatorColumn, $attributes)) {
            // Use key if present in discriminatorMap
            $map = $this->entityMap->getDiscriminatorColumnMap();

            $type = array_search($entityClass, $map);

            if ($type === false) {
                // Use entity FQCN if no corresponding key is set
                $attributes[$discriminatorColumn] = $entityClass;
            } else {
                $attributes[$discriminatorColumn] = $type;
            }
        }

        return $attributes;
    }

    /**
     * Convert Value Objects to raw db attributes.
     *
     * @param array $attributes
     *
     * @return array
     */
    protected function flattenEmbeddables(array $attributes): array
    {
        // TODO : deprecate old implementation
        $embeddables = $this->entityMap->getEmbeddables();

        foreach ($embeddables as $localKey => $embed) {
            // Retrieve the value object from the entity's attributes
            $valueObject = $attributes[$localKey];

            // Unset the corresponding key
            unset($attributes[$localKey]);

            // TODO Make wrapper object compatible with value objects
            $valueObjectAttributes = $valueObject->getEntityAttributes();

            $voMap = $this->getMapper()->getManager()->getValueMap($embed);
            $valueObjectAttributes = $voMap->getColumnNamesFromAttributes($valueObjectAttributes);

            // Now (if setup in the entity map) we prefix the value object's
            // attributes with the snake_case name of the embedded class.
            $prefix = snake_case(class_basename($embed));

            foreach ($valueObjectAttributes as $key => $value) {
                $valueObjectAttributes[$prefix.'_'.$key] = $value;
                unset($valueObjectAttributes[$key]);
            }

            $attributes = array_merge($attributes, $valueObjectAttributes);
        }

        //*********************
        // New implementation
        // *****************->

        $embeddedRelations = $this->entityMap->getEmbeddedRelationships();

        foreach ($embeddedRelations as $relation) {
            // Spawn a new instance we can pass to the relationship method
            $parentInstance = $this->getMapper()->newInstance();
            $relationInstance = $this->entityMap->$relation($parentInstance);

            // Extract the object from the attributes
            $embeddedObject = $attributes[$relation];

            unset($attributes[$relation]);

            $attributes = $relationInstance->normalize($embeddedObject) + $attributes;
        }

        return $attributes;
    }

    /**
     * Return's entity raw attributes in the state they were at last
     * query.
     *
     * @param array|null $columns
     *
     * @return array
     */
    protected function getCachedRawAttributes(array $columns = null): array
    {
        $cachedAttributes = $this->getCache()->get($this->getEntityKeyValue());

        if (is_null($columns)) {
            return $cachedAttributes;
        }

        return array_only($cachedAttributes, $columns);
    }

    /**
     * Return a single attribute from the cache.
     *
     * @param string $key
     *
     * @return mixed|null
     */
    protected function getCachedAttribute($key)
    {
        $cachedAttributes = $this->getCache()->get($this->getEntityKeyValue());

        if (array_key_exists($key, $cachedAttributes)) {
            return $cachedAttributes[$key];
        }
    }

    /**
     * Convert related Entity's attributes to foreign keys.
     *
     * @return array
     */
    public function getForeignKeyAttributes(): array
    {
        $foreignKeys = [];

        foreach ($this->entityMap->getLocalRelationships() as $relation) {
            // If the actual relationship is a non-loaded proxy, we'll simply retrieve
            // the foreign key pair without parsing the actual object. This will allow
            // user to modify the actual related ID's directly by updating the corresponding
            // attribute.
            if ($this->isNonLoadedProxy($relation)) {
                $foreignKeys = $foreignKeys + $this->getForeignKeyAttributesFromNonLoadedRelation($relation);
                continue;
            }

            // check if relationship has been parsed, meaning it has an actual object
            // in the entity's attributes
            if ($this->isActualRelationships($relation)) {
                $foreignKeys = $foreignKeys + $this->getForeignKeyAttributesFromRelation($relation);
            } else {
                $foreignKeys = $foreignKeys + $this->getNullForeignKeyFromRelation($relation);
            }
        }

        if (!is_null($this->parent)) {
            $foreignKeys = $this->getForeignKeyAttributesFromParent() + $foreignKeys;
        }

        return $foreignKeys;
    }

    /**
     * Get a null foreign key value pair for an empty relationship.
     *
     * @param string $relation
     *
     * @throws MappingException
     *
     * @return array
     */
    protected function getNullForeignKeyFromRelation(string $relation): array
    {
        $key = $this->entityMap->getLocalKeys($relation);

        if (is_array($key)) {
            return $this->entityMap->getEmptyValueForLocalKey($relation);
        }

        if (is_null($key)) {
            throw new MappingException("Foreign key for relation $relation cannot be null");
        }

        return [
            $key => $this->entityMap->getEmptyValueForLocalKey($relation),
        ];
    }

    /**
     * Return an associative array containing the key-value pair(s) from
     * the related entity.
     *
     * @param string $relation
     *
     * @return array
     */
    protected function getForeignKeyAttributesFromRelation(string $relation): array
    {
        // Call Relationship's method
        $relationship = $this->entityMap->$relation($this->getEntityObject());

        $relatedAggregate = $this->relationships[$relation][0];

        return $relationship->getForeignKeyValuePair($relatedAggregate->getEntityObject());
    }

    /**
     * Return an associative array containing the key-value pair(s) from
     * the foreign key attribute.
     *
     * @param string $relation
     *
     * @return array
     */
    protected function getForeignKeyAttributesFromNonLoadedRelation(string $relation): array
    {
        $keys = $this->entityMap->getLocalKeys($relation);

        // We'll treat single and composite keys (polymorphic) the same way.
        if (!is_array($keys)) {
            $keys = [$keys];
        }

        $foreignKey = [];

        foreach ($keys as $key) {
            $foreignKey[$key] = $this->getEntityAttribute($key);
        }

        return $foreignKey;
    }

    /**
     * Get foreign key attribute(s) from a parent entity in this
     * aggregate context.
     *
     * @return array
     */
    protected function getForeignKeyAttributesFromParent(): array
    {
        $parentMap = $this->parent->getEntityMap();

        $parentForeignRelations = $parentMap->getForeignRelationships();
        $parentPivotRelations = $parentMap->getPivotRelationships();

        // The parentRelation is the name of the relationship
        // methods on the parent entity map
        $parentRelation = $this->parentRelationship;

        if (in_array($parentRelation, $parentForeignRelations) &&
            !in_array($parentRelation, $parentPivotRelations)
        ) {
            $parentObject = $this->parent->getEntityObject();

            // Call Relationship's method on parent map
            $relationship = $parentMap->$parentRelation($parentObject);

            return $relationship->getForeignKeyValuePair($parentObject);
        }

        return [];
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

        foreach ($pivots as $pivot) {
            if (array_key_exists($pivot, $this->relationships)) {
                $this->updatePivotRelation($pivot);
            }
        }
    }

    /**
     * Update Single pivot relationship.
     *
     * @param string $relation
     *
     * @return void
     */
    protected function updatePivotRelation(string $relation)
    {
        $hashes = $this->getEntityHashesFromRelation($relation);

        $cachedAttributes = $this->getCachedRawAttributes();

        if (array_key_exists($relation, $cachedAttributes)) {
            // Compare the two array of hashes to find out existing
            // pivot records, and the ones to be created.
            $new = array_diff($hashes, array_keys($cachedAttributes[$relation]));
            $existing = array_intersect($hashes, array_keys($cachedAttributes[$relation]));
        } else {
            $existing = [];
            $new = $hashes;
        }

        if (count($new) > 0) {
            $pivots = $this->getRelatedAggregatesFromHashes($new, $relation);

            $this->entityMap->$relation($this->getEntityObject())->createPivots($pivots);
        }

        if (count($existing) > 0) {
            foreach ($existing as $pivotHash) {
                $this->updatePivotIfDirty($pivotHash, $relation);
            }
        }
    }

    /**
     * Compare existing pivot record in cache and update it
     * if the pivot attributes are dirty.
     *
     * @param string $pivotHash
     * @param string $relation
     *
     * @return void
     */
    protected function updatePivotIfDirty(string $pivotHash, string $relation)
    {
        $aggregate = $this->getRelatedAggregateFromHash($pivotHash, $relation);

        if ($aggregate->hasAttribute('pivot')) {
            $pivot = $aggregate->getEntityAttribute('pivot')->getEntityAttributes();

            $cachedPivotAttributes = $this->getPivotAttributesFromCache($pivotHash, $relation);

            $actualPivotAttributes = array_only($pivot, array_keys($cachedPivotAttributes));

            $dirty = $this->getDirtyAttributes($actualPivotAttributes, $cachedPivotAttributes);

            if (count($dirty) > 0) {
                $id = $aggregate->getEntityKeyValue();

                $this->entityMap->$relation($this->getEntityObject())->updateExistingPivot($id, $dirty);
            }
        }
    }

    /**
     * Compare two attributes array and return dirty attributes.
     *
     * @param array $actual
     * @param array $cached
     *
     * @return array
     */
    protected function getDirtyAttributes(array $actual, array $cached): array
    {
        $dirty = [];

        foreach ($actual as $key => $value) {
            if (!$this->originalIsNumericallyEquivalent($value, $cached[$key])) {
                $dirty[$key] = $actual[$key];
            }
        }

        return $dirty;
    }

    /**
     * @param string $pivotHash
     * @param string $relation
     *
     * @return array|null
     */
    protected function getPivotAttributesFromCache(string $pivotHash, string $relation)
    {
        $cachedAttributes = $this->getCachedRawAttributes();

        $cachedRelations = $cachedAttributes[$relation];

        foreach ($cachedRelations as $cachedRelation) {
            if ($cachedRelation == $pivotHash) {
                return $cachedRelation->getPivotAttributes();
            }
        }
    }

    /**
     * Returns an array of related Aggregates from its entity hashes.
     *
     * @param array  $hashes
     * @param string $relation
     *
     * @return array
     */
    protected function getRelatedAggregatesFromHashes(array $hashes, string $relation): array
    {
        $related = [];

        foreach ($hashes as $hash) {
            $aggregate = $this->getRelatedAggregateFromHash($hash, $relation);

            if ($aggregate) {
                $related[] = $aggregate;
            }
        }

        return $related;
    }

    /**
     * Get related aggregate from its hash.
     *
     * @param string $hash
     * @param string $relation
     *
     * @return \Analogue\ORM\System\Aggregate|null
     */
    protected function getRelatedAggregateFromHash(string $hash, string $relation)
    {
        foreach ($this->relationships[$relation] as $aggregate) {
            if ($aggregate->getEntityHash() == $hash) {
                return $aggregate;
            }
        }
    }

    /**
     * Return an array of Entity Hashes from a specific relation.
     *
     * @param string $relation
     *
     * @return array
     */
    protected function getEntityHashesFromRelation(string $relation): array
    {
        return array_map(function (self $aggregate) {
            return $aggregate->getEntityHash();
        }, $this->relationships[$relation]);
    }

    /**
     * Check the existence of an actual relationship.
     *
     * @param string $relation
     *
     * @return bool
     */
    protected function isActualRelationships(string $relation): bool
    {
        return array_key_exists($relation, $this->relationships)
            && count($this->relationships[$relation]) > 0;
    }

    /**
     * Return cache instance for the current entity type.
     *
     * @return \Analogue\ORM\System\Cache\AttributeCache
     */
    protected function getCache(): AttributeCache
    {
        return $this->getMapper()->getEntityCache();
    }

    /**
     * Get Only Raw Entity attributes which have been modified
     * since last query.
     *
     * @return array
     */
    public function getDirtyRawAttributes(): array
    {
        $attributes = $this->getRawAttributes();

        $cachedAttributes = $this->getCachedRawAttributes(array_keys($attributes));

        $dirty = [];

        foreach ($attributes as $key => $value) {
            if ($this->isActualRelation($key) || $key == 'pivot') {
                continue;
            }

            if (!array_key_exists($key, $cachedAttributes) && !$value instanceof Pivot) {
                $dirty[$key] = $value;
            } elseif ($value !== $cachedAttributes[$key] &&
                !$this->originalIsNumericallyEquivalent($value, $cachedAttributes[$key])) {
                $dirty[$key] = $value;
            }
        }

        return $dirty;
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    protected function isActualRelation(string $key): bool
    {
        return in_array($key, $this->entityMap->getNonEmbeddedRelationships());
    }

    /**
     * Return true if attribute is a non-loaded proxy.
     *
     * @param string $key
     *
     * @return bool
     */
    protected function isNonLoadedProxy(string $key): bool
    {
        $relation = $this->getEntityAttribute($key);

        return $relation instanceof ProxyInterface && !$relation->isProxyInitialized();
    }

    /**
     * Determine if the new and old values for a given key are numerically equivalent.
     *
     * @param $current
     * @param $original
     *
     * @return bool
     */
    protected function originalIsNumericallyEquivalent($current, $original): bool
    {
        return is_numeric($current) && is_numeric($original) && strcmp((string) $current, (string) $original) === 0;
    }

    /**
     * Get the underlying entity object.
     *
     * @return mixed
     */
    public function getEntityObject()
    {
        return $this->wrappedEntity->getObject();
    }

    /**
     * Return the Mapper instance for the current Entity Type.
     *
     * @return \Analogue\ORM\System\Mapper
     */
    public function getMapper(): Mapper
    {
        return Manager::getMapper($this->class);
    }

    /**
     * Check that the entity already exists in the database, by checking
     * if it has an EntityCache record.
     *
     * @return bool
     */
    public function exists(): bool
    {
        return $this->getCache()->has($this->getEntityKeyValue());
    }

    /**
     * {@inheritdoc}
     */
    public function setEntityAttributes(array $attributes)
    {
        $this->wrappedEntity->setEntityAttributes($attributes);
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityAttributes(): array
    {
        return $this->wrappedEntity->getEntityAttributes();
    }

    /**
     * {@inheritdoc}
     */
    public function setEntityAttribute(string $key, $value)
    {
        $this->wrappedEntity->setEntityAttribute($key, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityAttribute(string $key)
    {
        return $this->wrappedEntity->getEntityAttribute($key);
    }

    /**
     * {@inheritdoc}
     */
    public function hasAttribute(string $key): bool
    {
        return $this->wrappedEntity->hasAttribute($key);
    }

    /**
     * Return wrapped entity.
     *
     * @return InternallyMappable
     */
    public function getWrappedEntity()
    {
        return $this->wrappedEntity;
    }

    /**
     * Set the lazy loading proxies on the wrapped entity.
     *
     * @return void
     */
    public function setProxies()
    {
        $this->wrappedEntity->setProxies();
    }
}
