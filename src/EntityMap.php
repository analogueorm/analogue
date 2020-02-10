<?php

namespace Analogue\ORM;

use Analogue\ORM\Exceptions\MappingException;
use Analogue\ORM\Relationships\BelongsTo;
use Analogue\ORM\Relationships\BelongsToMany;
use Analogue\ORM\Relationships\EmbedsMany;
use Analogue\ORM\Relationships\EmbedsOne;
use Analogue\ORM\Relationships\HasMany;
use Analogue\ORM\Relationships\HasManyThrough;
use Analogue\ORM\Relationships\HasOne;
use Analogue\ORM\Relationships\MorphMany;
use Analogue\ORM\Relationships\MorphOne;
use Analogue\ORM\Relationships\MorphTo;
use Analogue\ORM\Relationships\MorphToMany;
use Analogue\ORM\System\Manager;
use Analogue\ORM\System\Wrappers\Factory;
use Exception;
use Illuminate\Support\Collection;
use ReflectionClass;

/**
 * The Entity Map defines the Mapping behaviour of an Entity,
 * including relationships.
 */
class EntityMap
{
    /**
     * The mapping driver to use with this entity.
     *
     * @var string
     */
    protected $driver = 'illuminate';

    /**
     * The Database Connection name for the model.
     *
     * @var string
     */
    protected $connection;

    /**
     * The table associated with the entity.
     *
     * @var string
     */
    protected $table;

    /**
     * The primary key for the model. If the model is an Embedded Value object
     * primary key is set to null.
     *
     * @var string|null
     */
    protected $primaryKey = 'id';

    /**
     * Name of the entity's array property that should
     * contain the attributes.
     * If set to null, analogue will only hydrate object's properties.
     *
     * @var string|null
     */
    protected $arrayName = 'attributes';

    /**
     * Array containing the list of database columns to be mapped
     * in the attributes array of the entity.
     *
     * @var array
     */
    protected $attributes = [];

    /**
     * Array containing the list of database columns to be mapped
     * to the entity's class properties.
     *
     * @var array
     */
    protected $properties = [];

    /**
     * The Custom Domain Class to use with this mapping.
     *
     * @var string
     */
    protected $class;

    /**
     * The event map for the entity.
     *
     * @var array
     */
    protected $events = [];

    /**
     * Embedded Value Objects.
     *
     * @deprecated 5.5 (use embedsOne() or embedsMany() relationships instead)
     *
     * @var array
     */
    protected $embeddables = [];

    /**
     * Determine the relationships method used on the entity.
     * If not set, mapper will autodetect them.
     *
     * @var array
     */
    protected $relationships = [];

    /**
     * Relationships that should be treated as collection.
     *
     * @var array
     */
    protected $manyRelations = [];

    /**
     * Relationships that should be treated as single entity.
     *
     * @var array
     */
    protected $singleRelations = [];

    /**
     * Relationships for which the key is stored in the Entity itself.
     *
     * @var array
     */
    protected $localRelations = [];

    /**
     * List of local keys associated to local relation methods.
     *
     * @var array
     */
    protected $localForeignKeys = [];

    /**
     * Relationships for which the key is stored in the Related Entity.
     *
     * @var array
     */
    protected $foreignRelations = [];

    /**
     * Relationships which use a pivot record.
     *
     * @var array
     */
    protected $pivotRelations = [];

    /**
     * Polymorphic relationships.
     *
     * @var array
     */
    protected $polymorphicRelations = [];

    /**
     * Dynamic relationships.
     *
     * @var array
     */
    protected $dynamicRelationships = [];

    /**
     * Targeted class for the relationship method. value is set to `null` for
     * polymorphic relations.
     *
     * @var array
     */
    protected $relatedClasses = [];

    /**
     * Some relation methods like embedded objects, or HasOne and MorphOne,
     * will never have a proxy loaded on them.
     *
     * @var array
     */
    protected $nonProxyRelationships = [];

    /**
     * Relation methods that are embedded objects.
     *
     * @var array
     */
    protected $embeddedRelations = [];

    /**
     * The number of models to return for pagination.
     *
     * @var int
     */
    protected $perPage = 15;

    /**
     * The relations to eager load on every query.
     *
     * @var array
     */
    protected $with = [];

    /**
     * The class name to be used in polymorphic relations.
     *
     * @var string
     */
    protected $morphClass;

    /**
     * Indicates if the entity should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The name of the "created at" attribute.
     *
     * @var string
     */
    protected $createdAtColumn = 'created_at';

    /**
     * The name of the "updated at" attribute.
     *
     * @var string
     */
    protected $updatedAtColumn = 'updated_at';

    /**
     * Indicates if the entity uses softdeletes.
     *
     * @var bool
     */
    public $softDeletes = false;

    /**
     * The name of the "deleted at" attribute.
     *
     * @var string
     */
    protected $deletedAtColumn = 'deleted_at';

    /**
     * The date format to use with the current database connection.
     *
     * @var string
     */
    protected $dateFormat;

    /**
     * Set this property to true if the entity should be instantiated
     * using the IoC Container.
     *
     * @var bool
     */
    protected $dependencyInjection = false;

    /**
     * Set the usage of inheritance, possible values are :
     * "single_table"
     * null.
     *
     * @var string
     */
    protected $inheritanceType;

    /**
     * Discriminator column name.
     *
     * @var string
     */
    protected $discriminatorColumn = 'type';

    /**
     * Allow using a string to define which entity type should be instantiated.
     * If not set, analogue will uses entity's FQCN.
     *
     * @var array
     */
    protected $discriminatorColumnMap = [];

    /**
     * Indicate if the entity map has been booted.
     *
     * @var bool
     */
    private $isBooted = false;

    /**
     * Set this property to true if you wish to use camel case
     * properties.
     *
     * @var bool
     */
    protected $camelCaseHydratation = false;

    /**
     * Return Domain class attributes, useful when mapping to a Plain PHP Object.
     *
     * @return array
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Set the domain class attributes.
     *
     * @param array $attributeNames
     */
    public function setAttributes(array $attributeNames)
    {
        $this->attributes = $attributeNames;
    }

    /**
     * Return true if the Entity has an 'attributes' array property.
     *
     * @return bool
     */
    public function usesAttributesArray(): bool
    {
        if ($this->arrayName === null) {
            return false;
        }

        if ($this->attributes === null) {
            return false;
        }

        return true;
    }

    /**
     * Return the name of the Entity's attributes property.
     *
     * @return string|null
     */
    public function getAttributesArrayName()
    {
        return $this->arrayName;
    }

    /**
     * Get all the attribute names for the class, including relationships, embeddables and primary key.
     *
     * @return array
     */
    public function getCompiledAttributes(): array
    {
        $key = $this->getKeyName();

        $embeddables = array_keys($this->getEmbeddables());

        $relationships = $this->getRelationships();

        $attributes = $this->getAttributes();

        return array_merge([$key], $embeddables, $relationships, $attributes);
    }

    /**
     * Set the date format to use with the current database connection.
     *
     * @param string $format
     */
    public function setDateFormat(string $format)
    {
        $this->dateFormat = $format;
    }

    /**
     * Get the date format to use with the current database connection.
     *
     *  @return string
     */
    public function getDateFormat(): string
    {
        return $this->dateFormat;
    }

    /**
     * Set the Driver for this mapping.
     *
     * @param string $driver
     */
    public function setDriver(string $driver)
    {
        $this->driver = $driver;
    }

    /**
     * Get the Driver for this mapping.
     *
     * @return string
     */
    public function getDriver(): string
    {
        return $this->driver;
    }

    /**
     * Set the db connection to use on the table.
     *
     * @param string $connection
     */
    public function setConnection(string $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Get the Database connection the Entity is stored on.
     *
     * @return string|null
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Get the table associated with the entity.
     *
     * @return string
     */
    public function getTable(): string
    {
        if (isset($this->table)) {
            return $this->table;
        }

        return str_replace('\\', '', snake_case(str_plural(class_basename($this->getClass()))));
    }

    /**
     * Set the database table name.
     *
     * @param string $table
     */
    public function setTable(string $table)
    {
        $this->table = $table;
    }

    /**
     * Get the custom entity class.
     *
     * @return string|null
     */
    public function getClass()
    {
        return $this->class ?: null;
    }

    /**
     * Set the custom entity class.
     *
     * @param string $class The FQCN
     */
    public function setClass(string $class)
    {
        $this->class = $class;
    }

    /**
     * Get the embedded Value Objects.
     *
     * @deprecated 5.5
     *
     * @return array
     */
    public function getEmbeddables(): array
    {
        return $this->embeddables;
    }

    /**
     * Return attributes that should be mapped to class properties.
     *
     * @return array
     */
    public function getProperties(): array
    {
        return get_parent_class(__CLASS__) !== false
            ? array_unique(array_merge($this->properties, parent::getProperties()))
            : $this->properties;
    }

    /**
     * Return event map for the Entity.
     *
     * @return array
     */
    public function getEvents(): array
    {
        return $this->events;
    }

    /**
     * Return the array property in which will be mapped all attributes
     * that are not mapped to class properties.
     *
     * @return string
     */
    public function getAttributesPropertyName(): string
    {
    }

    /**
     * Set the embedded Value Objects.
     *
     * @deprecated 5.5
     *
     * @param array $embeddables
     */
    public function setEmbeddables(array $embeddables)
    {
        $this->embeddables = $embeddables;
    }

    /**
     * Get the relationships to map on a custom domain
     * class.
     *
     * @return array
     */
    public function getRelationships(): array
    {
        return $this->relationships;
    }

    /**
     * Return all relationships that are not embedded objects.
     *
     * @return array
     */
    public function getNonEmbeddedRelationships(): array
    {
        return array_diff($this->relationships, $this->embeddedRelations);
    }

    /**
     * Get the relationships that will not have a proxy
     * set on them.
     *
     * @return array
     */
    public function getRelationshipsWithoutProxy(): array
    {
        return $this->nonProxyRelationships;
    }

    /**
     * Relationships of the Entity type.
     *
     * @return array
     */
    public function getSingleRelationships(): array
    {
        return $this->singleRelations;
    }

    /**
     * Return true if relationship is single.
     *
     * @param string $relation
     *
     * @return bool
     */
    public function isSingleRelationship(string $relation): bool
    {
        return in_array($relation, $this->singleRelations);
    }

    /**
     * Relationships of type Collection.
     *
     * @return array
     */
    public function getManyRelationships(): array
    {
        return $this->manyRelations;
    }

    /**
     * Return true if relationship is single.
     *
     * @param string $relation
     *
     * @return bool
     */
    public function isManyRelationship(string $relation): bool
    {
        return in_array($relation, $this->manyRelations);
    }

    /**
     * Return empty value for a given relationship.
     *
     * @param string $relation
     *
     * @throws MappingException
     *
     * @return mixed
     */
    public function getEmptyValueForRelationship(string $relation)
    {
        if ($this->isSingleRelationship($relation)) {
            return;
        }

        if ($this->isManyRelationship($relation)) {
            return new Collection();
        }

        throw new MappingException("Cannot determine default value of $relation");
    }

    /**
     * Return empty value for a local foreign key.
     *
     * @param string $relation
     *
     * @return mixed
     */
    public function getEmptyValueForLocalKey(string $relation)
    {
        if ($this->isPolymorphic($relation)) {
            $key = $this->localForeignKeys[$relation];

            return [
                $key['type'] => null,
                $key['id']   => null,
            ];
        }

        if ($this->isManyRelationship($relation)) {
            return [];
        }
    }

    /**
     * Relationships with foreign key in the mapped entity record.
     *
     * @return array
     */
    public function getLocalRelationships(): array
    {
        return $this->localRelations;
    }

    /**
     * Return the local keys associated to the relationship.
     *
     * @param string $relation
     *
     * @return string|array|null
     */
    public function getLocalKeys($relation)
    {
        return isset($this->localForeignKeys[$relation]) ? $this->localForeignKeys[$relation] : null;
    }

    /**
     * Relationships with foreign key in the related Entity record.
     *
     * @return array
     */
    public function getForeignRelationships(): array
    {
        return $this->foreignRelations;
    }

    /**
     * Relationships which keys are stored in a pivot record.
     *
     * @return array
     */
    public function getPivotRelationships(): array
    {
        return $this->pivotRelations;
    }

    /**
     * Return an array containing all embedded relationships.
     *
     * @return array
     */
    public function getEmbeddedRelationships(): array
    {
        return $this->embeddedRelations;
    }

    /**
     * Return true if the relationship method is polymorphic.
     *
     * @param string $relation
     *
     * @return bool
     */
    public function isPolymorphic($relation): bool
    {
        return in_array($relation, $this->polymorphicRelations);
    }

    /**
     * Get the targeted type for a relationship. Return null if polymorphic.
     *
     * @param string $relation
     *
     * @return string|null
     */
    public function getTargettedClass($relation)
    {
        if (array_key_exists($relation, $this->relatedClasses)) {
            return $this->relatedClasses[$relation];
        }
    }

    /**
     * Add a Dynamic Relationship method at runtime. This has to be done
     * by hooking the 'initializing' event, before entityMap is initialized.
     *
     * @param string   $name         Relation name
     * @param \Closure $relationship
     *
     * @return void
     */
    public function addRelationshipMethod($name, \Closure $relationship)
    {
        $this->dynamicRelationships[$name] = $relationship;
    }

    /**
     * Get the dynamic relationship method names.
     *
     * @return array
     */
    public function getDynamicRelationships(): array
    {
        return array_keys($this->dynamicRelationships);
    }

    /**
     * Get the relationships that have to be eager loaded
     * on each request.
     *
     * @return array
     */
    public function getEagerloadedRelationships(): array
    {
        return $this->with;
    }

    /**
     * Get the primary key attribute for the entity.
     *
     * @return string|null
     */
    public function getKeyName()
    {
        return $this->primaryKey;
    }

    /**
     * Set the primary key for the entity.
     *
     * @param string $key
     *
     * @return void
     */
    public function setKeyName(string $key)
    {
        $this->primaryKey = $key;
    }

    /**
     * Get the table qualified key name.
     *
     * @return string
     */
    public function getQualifiedKeyName(): string
    {
        return $this->getTable().'.'.$this->getKeyName();
    }

    /**
     * Get the number of models to return per page.
     *
     * @return int
     */
    public function getPerPage(): int
    {
        return $this->perPage;
    }

    /**
     * Set the number of models to return per page.
     *
     * @param int $perPage
     *
     * @return void
     */
    public function setPerPage(int $perPage)
    {
        $this->perPage = $perPage;
    }

    /**
     * Determine if the entity uses get.
     *
     * @return bool
     */
    public function usesTimestamps(): bool
    {
        return $this->timestamps;
    }

    /**
     * Determine if the entity uses soft deletes.
     *
     * @return bool
     */
    public function usesSoftDeletes(): bool
    {
        return $this->softDeletes;
    }

    /**
     * Get the 'created_at' column name.
     *
     * @return string
     */
    public function getCreatedAtColumn(): string
    {
        return $this->createdAtColumn;
    }

    /**
     * Get the 'updated_at' column name.
     *
     * @return string
     */
    public function getUpdatedAtColumn(): string
    {
        return $this->updatedAtColumn;
    }

    /**
     * Get the deleted_at column.
     *
     * @return string
     */
    public function getQualifiedDeletedAtColumn(): string
    {
        return $this->deletedAtColumn;
    }

    /**
     * Get the default foreign key name for the model.
     *
     * @return string
     */
    public function getForeignKey(): string
    {
        return snake_case(class_basename($this->getClass())).'_id';
    }

    /**
     * Return the inheritance type used by the entity.
     *
     * @return string|null
     */
    public function getInheritanceType()
    {
        return $this->inheritanceType;
    }

    /**
     * Return the discriminator column name on the entity that's
     * used for table inheritance.
     *
     * @return string
     */
    public function getDiscriminatorColumn(): string
    {
        return $this->discriminatorColumn;
    }

    /**
     * Return the mapping of discriminator column values to
     * entity class names that are used for table inheritance.
     *
     * @return array
     */
    public function getDiscriminatorColumnMap(): array
    {
        return $this->discriminatorColumnMap;
    }

    /**
     * Return true if the entity should be instantiated using
     * the IoC Container.
     *
     * @return bool
     */
    public function useDependencyInjection(): bool
    {
        return $this->dependencyInjection;
    }

    /**
     * Add a single relation method name once.
     *
     * @param string $relation
     */
    protected function addSingleRelation(string $relation)
    {
        if (!in_array($relation, $this->singleRelations)) {
            $this->singleRelations[] = $relation;
        }
    }

    /**
     * Add a foreign relation method name once.
     *
     * @param string $relation
     */
    protected function addForeignRelation(string $relation)
    {
        if (!in_array($relation, $this->foreignRelations)) {
            $this->foreignRelations[] = $relation;
        }
    }

    /**
     * Add a polymorphic relation method name once.
     *
     * @param string $relation
     */
    protected function addPolymorphicRelation(string $relation)
    {
        if (!in_array($relation, $this->polymorphicRelations)) {
            $this->polymorphicRelations[] = $relation;
        }
    }

    /**
     * Add a non proxy relation method name once.
     *
     * @param string $relation
     */
    protected function addNonProxyRelation(string $relation)
    {
        if (!in_array($relation, $this->nonProxyRelationships)) {
            $this->nonProxyRelationships[] = $relation;
        }
    }

    /**
     * Add a local relation method name once.
     *
     * @param string $relation
     */
    protected function addLocalRelation(string $relation)
    {
        if (!in_array($relation, $this->localRelations)) {
            $this->localRelations[] = $relation;
        }
    }

    /**
     * Add a many relation method name once.
     *
     * @param string $relation
     */
    protected function addManyRelation(string $relation)
    {
        if (!in_array($relation, $this->manyRelations)) {
            $this->manyRelations[] = $relation;
        }
    }

    /**
     * Add a pivot relation method name once.
     *
     * @param string $relation
     */
    protected function addPivotRelation(string $relation)
    {
        if (!in_array($relation, $this->pivotRelations)) {
            $this->pivotRelations[] = $relation;
        }
    }

    /**
     * Add an embedded relation.
     *
     * @param string $relation
     */
    protected function addEmbeddedRelation(string $relation)
    {
        if (!in_array($relation, $this->embeddedRelations)) {
            $this->embeddedRelations[] = $relation;
        }
    }

    /**
     * Define an Embedded Object.
     *
     * @param mixed  $parent
     * @param string $relatedClass
     * @param string $relation
     *
     * @return EmbedsOne
     */
    public function embedsOne($parent, string $relatedClass, string $relation = null): EmbedsOne
    {
        if (is_null($relation)) {
            list(, $caller) = debug_backtrace(false);
            $relation = $caller['function'];
        }

        $this->addEmbeddedRelation($relation);
        $this->addNonProxyRelation($relation);

        return new EmbedsOne($parent, $relatedClass, $relation);
    }

    /**
     * Define an Embedded Collection.
     *
     * @param mixed  $parent
     * @param string $relatedClass
     * @param string $relation
     *
     * @return EmbedsMany
     */
    public function embedsMany($parent, string $relatedClass, string $relation = null): EmbedsMany
    {
        if (is_null($relation)) {
            list(, $caller) = debug_backtrace(false);
            $relation = $caller['function'];
        }

        $this->addEmbeddedRelation($relation);
        $this->addNonProxyRelation($relation);

        return new EmbedsMany($parent, $relatedClass, $relation);
    }

    /**
     * Define a one-to-one relationship.
     *
     * @param mixed  $entity
     * @param string $related    entity class
     * @param string $foreignKey
     * @param string $localKey
     *
     * @throws MappingException
     *
     * @return \Analogue\ORM\Relationships\HasOne
     */
    public function hasOne($entity, string $related, string $foreignKey = null, string $localKey = null): HasOne
    {
        $foreignKey = $foreignKey ?: $this->getForeignKey();

        $relatedMapper = Manager::getInstance()->mapper($related);

        $relatedMap = $relatedMapper->getEntityMap();

        $localKey = $localKey ?: $this->getKeyName();

        // Add the relation to the definition in map
        list(, $caller) = debug_backtrace(false);
        $relation = $caller['function'];
        $this->relatedClasses[$relation] = $related;

        $this->addSingleRelation($relation);
        $this->addForeignRelation($relation);
        $this->addNonProxyRelation($relation);

        // This relationship will always be eager loaded, as proxying it would
        // mean having an object that doesn't actually exists.
        if (!in_array($relation, $this->with)) {
            $this->with[] = $relation;
        }

        return new HasOne($relatedMapper, $entity, /*$relatedMap->getTable().'.'*/$foreignKey, $localKey);
    }

    /**
     * Define a polymorphic one-to-one relationship.
     *
     * @param mixed       $entity
     * @param string      $related
     * @param string      $name
     * @param string|null $type
     * @param string|null $id
     * @param string|null $localKey
     *
     * @throws MappingException
     *
     * @return \Analogue\ORM\Relationships\MorphOne
     */
    public function morphOne(
        $entity,
        string $related,
        string $name,
        string $type = null,
        string $id = null,
        string $localKey = null
    ): MorphOne {
        list($type, $id) = $this->getMorphs($name, $type, $id);

        $localKey = $localKey ?: $this->getKeyName();

        $relatedMapper = Manager::getInstance()->mapper($related);

        //$table = $relatedMapper->getEntityMap()->getTable();

        // Add the relation to the definition in map
        list(, $caller) = debug_backtrace(false);
        $relation = $caller['function'];
        $this->relatedClasses[$relation] = $related;

        $this->addSingleRelation($relation);
        $this->addForeignRelation($relation);
        $this->addNonProxyRelation($relation);

        // This relationship will always be eager loaded, as proxying it would
        // mean having an object that doesn't actually exists.
        if (!in_array($relation, $this->with)) {
            $this->with[] = $relation;
        }

        return new MorphOne($relatedMapper, $entity, /*$table.'.'.*/$type, /*$table.'.'.*/$id, $localKey);
    }

    /**
     * Define an inverse one-to-one or many relationship.
     *
     * @param mixed       $entity
     * @param string      $related
     * @param string|null $foreignKey
     * @param string|null $otherKey
     *
     * @throws MappingException
     *
     * @return \Analogue\ORM\Relationships\BelongsTo
     */
    public function belongsTo($entity, string $related, string $foreignKey = null, string $otherKey = null): BelongsTo
    {
        // Add the relation to the definition in map
        list(, $caller) = debug_backtrace(false);
        $relation = $caller['function'];
        $this->relatedClasses[$relation] = $related;

        $this->addSingleRelation($relation);
        $this->addLocalRelation($relation);

        // If no foreign key was supplied, we can use a backtrace to guess the proper
        // foreign key name by using the name of the relationship function, which
        // when combined with an "_id" should conventionally match the columns.
        if (is_null($foreignKey)) {
            $foreignKey = snake_case($relation).'_id';
        }

        $this->localForeignKeys[$relation] = $foreignKey;

        $relatedMapper = Manager::getInstance()->mapper($related);

        $otherKey = $otherKey ?: $relatedMapper->getEntityMap()->getKeyName();

        return new BelongsTo($relatedMapper, $entity, $foreignKey, $otherKey, $relation);
    }

    /**
     * Define a polymorphic, inverse one-to-one or many relationship.
     *
     * @param mixed       $entity
     * @param string|null $name
     * @param string|null $type
     * @param string|null $id
     *
     * @throws MappingException
     *
     * @return \Analogue\ORM\Relationships\MorphTo
     */
    public function morphTo($entity, string $name = null, string $type = null, string $id = null): MorphTo
    {
        // If no name is provided, we will use the backtrace to get the function name
        // since that is most likely the name of the polymorphic interface. We can
        // use that to get both the class and foreign key that will be utilized.
        if (is_null($name)) {
            list(, $caller) = debug_backtrace(false);

            $name = snake_case($caller['function']);
        }
        $this->addSingleRelation($name);
        $this->addLocalRelation($name);
        $this->addPolymorphicRelation($name);

        $this->relatedClass[$name] = null;

        list($type, $id) = $this->getMorphs($name, $type, $id);

        // Store the foreign key in the entity map.
        // We might want to store the (key, type) as we might need it
        // to build a MorphTo proxy
        $this->localForeignKeys[$name] = [
            'id'   => $id,
            'type' => $type,
        ];

        $mapper = Manager::getInstance()->mapper(get_class($entity));

        // If the type value is null it is probably safe to assume we're eager loading
        // the relationship. When that is the case we will pass in a dummy query as
        // there are multiple types in the morph and we can't use single queries.
        $factory = new Factory();
        $wrapper = $factory->make($entity);

        if (is_null($class = $wrapper->getEntityAttribute($type))) {
            return new MorphTo(
                $mapper,
                $entity,
                $id,
                null,
                $type,
                $name
            );
        } else {
            // If we are not eager loading the relationship we will essentially treat this
            // as a belongs-to style relationship since morph-to extends that class and
            // we will pass in the appropriate values so that it behaves as expected.
            $class = Manager::getInstance()->getInverseMorphMap($class);
            $relatedMapper = Manager::getInstance()->mapper($class);

            $foreignKey = $relatedMapper->getEntityMap()->getKeyName();

            return new MorphTo(
                $relatedMapper,
                $entity,
                $id,
                $foreignKey,
                $type,
                $name
            );
        }
    }

    /**
     * Define a one-to-many relationship.
     *
     * @param mixed       $entity
     * @param string      $related
     * @param string|null $foreignKey
     * @param string|null $localKey
     *
     * @throws MappingException
     *
     * @return \Analogue\ORM\Relationships\HasMany
     */
    public function hasMany($entity, string $related, string $foreignKey = null, string $localKey = null): HasMany
    {
        $foreignKey = $foreignKey ?: $this->getForeignKey();

        $relatedMapper = Manager::getInstance()->mapper($related);

        $localKey = $localKey ?: $this->getKeyName();

        // Add the relation to the definition in map
        list(, $caller) = debug_backtrace(false);
        $relation = $caller['function'];
        $this->relatedClasses[$relation] = $related;

        $this->addManyRelation($relation);
        $this->addForeignRelation($relation);

        return new HasMany($relatedMapper, $entity, $foreignKey, $localKey);
    }

    /**
     * Define a has-many-through relationship.
     *
     * @param mixed       $entity
     * @param string      $related
     * @param string      $through
     * @param string|null $firstKey
     * @param string|null $secondKey
     *
     * @throws MappingException
     *
     * @return \Analogue\ORM\Relationships\HasManyThrough
     */
    public function hasManyThrough(
        $entity,
        string $related,
        string $through,
        string $firstKey = null,
        string $secondKey = null
    ): HasManyThrough {
        $relatedMapper = Manager::getInstance()->mapper($related);
        $throughMapper = Manager::getInstance()->mapper($through);

        $firstKey = $firstKey ?: $this->getForeignKey();

        $throughMap = $throughMapper->getEntityMap();

        $secondKey = $secondKey ?: $throughMap->getForeignKey();

        // Add the relation to the definition in map
        list(, $caller) = debug_backtrace(false);
        $relation = $caller['function'];
        $this->relatedClasses[$relation] = $related;

        $this->addManyRelation($relation);
        $this->addForeignRelation($relation);

        return new HasManyThrough($relatedMapper, $entity, $throughMap, $firstKey, $secondKey);
    }

    /**
     * Define a polymorphic one-to-many relationship.
     *
     * @param mixed       $entity
     * @param string      $related
     * @param string      $name
     * @param string|null $type
     * @param string|null $id
     * @param string|null $localKey
     *
     * @return \Analogue\ORM\Relationships\MorphMany
     */
    public function morphMany(
        $entity,
        string $related,
        string $name,
        string $type = null,
        string $id = null,
        string $localKey = null
    ): MorphMany {
        // Here we will gather up the morph type and ID for the relationship so that we
        // can properly query the intermediate table of a relation. Finally, we will
        // get the table and create the relationship instances for the developers.
        list($type, $id) = $this->getMorphs($name, $type, $id);

        $relatedMapper = Manager::getInstance()->mapper($related);

        $table = $relatedMapper->getEntityMap()->getTable();

        $localKey = $localKey ?: $this->getKeyName();

        // Add the relation to the definition in map
        list(, $caller) = debug_backtrace(false);
        $relation = $caller['function'];
        $this->relatedClasses[$relation] = $related;

        $this->addManyRelation($relation);
        $this->addForeignRelation($relation);

        return new MorphMany($relatedMapper, $entity, /*$table.'.'.*/$type, /*$table.'.'.*/$id, $localKey);
    }

    /**
     * Define a many-to-many relationship.
     *
     * @param mixed       $entity
     * @param string      $related
     * @param string|null $table
     * @param string|null $foreignKey
     * @param string|null $otherKey
     *
     * @throws MappingException
     *
     * @return \Analogue\ORM\Relationships\BelongsToMany
     */
    public function belongsToMany(
        $entity,
        string $related,
        string $table = null,
        string $foreignKey = null,
        string $otherKey = null
    ): BelongsToMany {
        // Add the relation to the definition in map
        list(, $caller) = debug_backtrace(false);
        $relation = $caller['function'];
        $this->relatedClasses[$relation] = $related;

        $this->addManyRelation($relation);
        $this->addForeignRelation($relation);
        $this->addPivotRelation($relation);

        // First, we'll need to determine the foreign key and "other key" for the
        // relationship. Once we have determined the keys we'll make the query
        // instances as well as the relationship instances we need for this.
        $foreignKey = $foreignKey ?: $this->getForeignKey();

        $relatedMapper = Manager::getInstance()->mapper($related);

        $relatedMap = $relatedMapper->getEntityMap();

        $otherKey = $otherKey ?: $relatedMap->getForeignKey();

        // If no table name was provided, we can guess it by concatenating the two
        // models using underscores in alphabetical order. The two model names
        // are transformed to snake case from their default CamelCase also.
        if (is_null($table)) {
            $table = $this->joiningTable($relatedMap);
        }

        return new BelongsToMany($relatedMapper, $entity, $table, $foreignKey, $otherKey, $relation);
    }

    /**
     * Define a polymorphic many-to-many relationship.
     *
     * @param mixed       $entity
     * @param string      $related
     * @param string      $name
     * @param string|null $table
     * @param string|null $foreignKey
     * @param string|null $otherKey
     * @param bool        $inverse
     *
     * @throws MappingException
     *
     * @return \Analogue\ORM\Relationships\MorphToMany
     */
    public function morphToMany(
        $entity,
        string $related,
        string $name,
        string $table = null,
        string $foreignKey = null,
        string $otherKey = null,
        bool $inverse = false
    ): MorphToMany {
        // Add the relation to the definition in map
        list(, $caller) = debug_backtrace(false);
        $relation = $caller['function'];
        $this->relatedClasses[$relation] = $related;

        $this->addManyRelation($relation);
        $this->addForeignRelation($relation);
        $this->addPivotRelation($relation);

        // First, we will need to determine the foreign key and "other key" for the
        // relationship. Once we have determined the keys we will make the query
        // instances, as well as the relationship instances we need for these.
        $foreignKey = $foreignKey ?: $name.'_id';

        $relatedMapper = Manager::getInstance()->mapper($related);

        $otherKey = $otherKey ?: $relatedMapper->getEntityMap()->getForeignKey();

        $table = $table ?: str_plural($name);

        return new MorphToMany($relatedMapper, $entity, $name, $table, $foreignKey, $otherKey, $caller, $inverse);
    }

    /**
     * Define a polymorphic, inverse many-to-many relationship.
     *
     * @param mixed       $entity
     * @param string      $related
     * @param string      $name
     * @param string|null $table
     * @param string|null $foreignKey
     * @param string|null $otherKey
     *
     * @throws MappingException
     *
     * @return \Analogue\ORM\Relationships\MorphToMany
     */
    public function morphedByMany(
        $entity,
        string $related,
        string $name,
        string $table = null,
        string $foreignKey = null,
        string $otherKey = null
    ): MorphToMany {
        // Add the relation to the definition in map
        list(, $caller) = debug_backtrace(false);
        $relation = $caller['function'];
        $this->relatedClasses[$relation] = $related;

        $this->addManyRelation($relation);
        $this->addForeignRelation($relation);

        $foreignKey = $foreignKey ?: $this->getForeignKey();

        // For the inverse of the polymorphic many-to-many relations, we will change
        // the way we determine the foreign and other keys, as it is the opposite
        // of the morph-to-many method since we're figuring out these inverses.
        $otherKey = $otherKey ?: $name.'_id';

        return $this->morphToMany($entity, $related, $name, $table, $foreignKey, $otherKey, true);
    }

    /**
     * Get the joining table name for a many-to-many relation.
     *
     * @param EntityMap $relatedMap
     *
     * @return string
     */
    public function joiningTable(self $relatedMap): string
    {
        // The joining table name, by convention, is simply the snake cased models
        // sorted alphabetically and concatenated with an underscore, so we can
        // just sort the models and join them together to get the table name.
        $base = $this->getTable();

        $related = $relatedMap->getTable();

        $tables = [$related, $base];

        // Now that we have the model names in an array we can just sort them and
        // use the implode function to join them together with an underscores,
        // which is typically used by convention within the database system.
        sort($tables);

        return strtolower(implode('_', $tables));
    }

    /**
     * Get the polymorphic relationship columns.
     *
     * @param string $name
     * @param string $type
     * @param string $id
     *
     * @return string[]
     */
    protected function getMorphs(string $name, string $type = null, string $id = null): array
    {
        return [
            $type ?: $name.'_type',
            $id ?: $name.'_id',
        ];
    }

    /**
     * Get the class name for polymorphic relations.
     *
     * @return string
     */
    public function getMorphClass(): string
    {
        $morphClass = Manager::getInstance()->getMorphMap($this->getClass());

        return $this->morphClass ?: $morphClass;
    }

    /**
     * Create a new Entity Collection instance.
     *
     * @param array $entities
     *
     * @return \Analogue\ORM\EntityCollection
     */
    public function newCollection(array $entities = []): EntityCollection
    {
        return new EntityCollection($entities);
    }

    /**
     * Process EntityMap parsing at initialization time.
     *
     * @return void
     */
    public function initialize()
    {
        $userMethods = $this->getCustomMethods();

        // Parse EntityMap for method based relationship
        if (count($userMethods) > 0) {
            $this->relationships = $this->parseMethodsForRelationship($userMethods);
        }

        // Parse EntityMap for dynamic relationships
        if (count($this->dynamicRelationships) > 0) {
            $this->relationships = $this->relationships + $this->getDynamicRelationships();
        }
    }

    /**
     * Parse every relationships on the EntityMap and sort
     * them by type.
     *
     * @return void
     */
    public function boot()
    {
        if (count($this->relationships) > 0) {
            $this->sortRelationshipsByType();
        }

        $this->isBooted = true;
    }

    /**
     * Return true if entity map has been booted.
     *
     * @return bool
     */
    public function isBooted(): bool
    {
        return $this->isBooted;
    }

    /**
     * Get Methods that has been added in the child class.
     *
     * @return array
     */
    protected function getCustomMethods(): array
    {
        $mapMethods = get_class_methods($this);

        $parentsMethods = get_class_methods('Analogue\ORM\EntityMap');

        return array_diff($mapMethods, $parentsMethods);
    }

    /**
     * Parse user's class methods for relationships.
     *
     * @param array $customMethods
     *
     * @return array
     */
    protected function parseMethodsForRelationship(array $customMethods): array
    {
        $relationships = [];

        $class = new ReflectionClass(get_class($this));

        // Get the mapped Entity class, as we will detect relationships
        // methods by testing that the first argument is type-hinted to
        // the same class as the mapped Entity.
        $entityClass = $this->getClass();

        foreach ($customMethods as $methodName) {
            $method = $class->getMethod($methodName);

            if ($method->getNumberOfParameters() > 0) {
                $params = $method->getParameters();

                if ($params[0]->getClass() && ($params[0]->getClass()->name == $entityClass || is_subclass_of($entityClass, $params[0]->getClass()->name))) {
                    $relationships[] = $methodName;
                }
            }
        }

        return $relationships;
    }

    /**
     * Sort Relationships methods by type.
     *
     * TODO : replace this by directly setting these value
     * in the corresponding methods, so we won't need
     * the correspondency table
     *
     * @return void
     */
    protected function sortRelationshipsByType()
    {
        $entityClass = $this->getClass();

        // Instantiate a dummy entity which we will pass to relationship methods.
        $entity = unserialize(sprintf('O:%d:"%s":0:{}', strlen($entityClass), $entityClass));

        foreach ($this->relationships as $relation) {
            $this->$relation($entity);
        }
    }

    /**
     * Override this method for custom entity instantiation.
     *
     * @return null
     *
     * @deprecated 5.5
     */
    public function activator()
    {
    }

    /**
     * Magic call to dynamic relationships.
     *
     * @param string $method
     * @param array  $parameters
     *
     * @throws Exception
     *
     * @return mixed
     */
    public function __call(string $method, array $parameters)
    {
        if (!array_key_exists($method, $this->dynamicRelationships)) {
            throw new Exception(get_class($this)." has no method $method");
        }

        // Add $this to parameters so the closure can call relationship method on the map.
        $parameters[] = $this;

        return  call_user_func_array($this->dynamicRelationships[$method], $parameters);
    }

    /**
     * Maps the names of the column names to the appropriate attributes
     * of an entity if the $attributes property of an EntityMap is an
     * associative array.
     *
     * @param array $array
     *
     * @return array
     */
    public function getAttributeNamesFromColumns($array)
    {
        if (!empty($this->mappings)) {
            $newArray = [];
            foreach ($array as $key => $value) {
                $attributeName = isset($this->mappings[$key]) ? $this->mappings[$key] : $key;
                $newArray[$attributeName] = $value;
            }

            return $newArray;
        }
        if ($this->camelCaseHydratation) {
            $newArray = [];
            foreach ($array as $key => $value) {
                $attributeName = camel_case($key);
                $newArray[$attributeName] = $value;
            }

            return $newArray;
        }

        return $array;
    }

    /**
     * Gets the entity attribute name of a given column in a table.
     *
     * @param string $columnName
     *
     * @return string
     */
    public function getAttributeNameForColumn($columnName)
    {
        if (!empty($this->mappings)) {
            if (isset($this->mappings[$columnName])) {
                return $this->mappings[$columnName];
            }
        }

        return $columnName;
    }

    /**
     * Maps the attribute names of an entity to the appropriate
     * column names in the database if the $attributes property of
     * an EntityMap is an associative array.
     *
     * @param array $array
     *
     * @return array
     */
    public function getColumnNamesFromAttributes($array)
    {
        if (!empty($this->mappings)) {
            $newArray = [];
            $flipped = array_flip($this->mappings);
            foreach ($array as $key => $value) {
                $attributeName = isset($flipped[$key]) ? $flipped[$key] : $key;
                $newArray[$attributeName] = $value;
            }

            return $newArray;
        }
        if ($this->camelCaseHydratation) {
            $newArray = [];
            foreach ($array as $key => $value) {
                $attributeName = snake_case($key);
                $newArray[$attributeName] = $value;
            }

            return $newArray;
        }

        return $array;
    }

    public function hasAttribute($attribute)
    {
        if (!empty($this->mappings)) {
            return in_array($attribute, array_values($this->mappings));
        }

        return in_array($attribute, $attributes);
    }
}
