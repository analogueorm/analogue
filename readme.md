(this project is looking for a new maintainer)

# Analogue ORM 
[![Latest Stable Version](https://poser.pugx.org/analogue/orm/v/stable)](https://packagist.org/packages/analogue/orm)
[![Latest Unstable Version](https://poser.pugx.org/analogue/orm/v/unstable)](https://packagist.org/packages/analogue/orm)
[![License](https://poser.pugx.org/analogue/orm/license)](https://packagist.org/packages/analogue/orm)
[![Build Status](https://travis-ci.org/analogueorm/analogue.svg?branch=5.6)](https://travis-ci.org/analogueorm/analogue.svg?branch=5.6)
[![StyleCI](https://styleci.io/repos/27265369/shield?branch=5.6)](https://styleci.io/repos/27265369)

**Analogue** is a flexible, easy-to-use **ORM** for **PHP**. It is a transposition of the **Eloquent** ORM that ships with **Laravel** framework using a **Data Mapper** pattern instead of the original Active Record approach. it overcomes some of Eloquent's architectural limitations by using a strict separation of concerns; for example, you can use **Value Objects** or **Single-table-inheritance**, which are hard/impossible to implement correctly using the native ORM. 

As a **Laravel package**, it integrates flawlessly inside the framework, and provides a more powerfull peristance layer, allowing to build enterprise-grade applications while retaining a simple and enjoyable development experience. 

## Installation

```bash
composer require analogue/orm
```

See [Configuration](https://github.com/analogueorm/analogue/wiki/Installation) for more information.

## Concept

The concept is simple; your model layer is defined using 2 classes : one **Entity**, which can be any PHP class or extends the base *Analogue\ORM\Entity* class which provides magic getters and setters, and one **EntityMap** which defines relationships, castings, table name, database column names. 

Take this simple domain model : 

```php
use Analogue\ORM\Entity;
use Illuminate\Support\Collection;

class Blog extends Entity
{
    public function __construct()
    {
        $this->posts = new Collection;
    }

    public function addPost(Post $post)
    {
        $this->posts->push($post);
    }
}

class Post extends Entity
{
 
}

```

We can instruct **Analogue** how these objects are related using these classes : 

```php
use Analogue\ORM\EntityMap;

class BlogMap extends EntityMap
{
    public function posts(Blog $blog)
    {
        return $this->hasMany($blog, Post::class);
    }
}

class PostMap extends EntityMap
{
    public function blog(Post $post)
    {
        return $this->belongsTo($post, Blog::class);
    }
}

```

Now we can create related instance of or object and persist them to the database : 

```php
$blog = new Blog;
$blog->title = "My first blog";

$post = new Post; 
$post->title->"My first post";

$blog->addPost($post);

// Only the blog instance need to explicitely stored; Analogue takes care of synchronizing
// related objects behinds the scene. 

mapper(Blog::class)->store($blog);

```

Once our objects are persisted into the database, we can query them using the fluent query builder : 

```php
$blog = mapper(Blog::class)->first();

echo $blog->posts->first()->title; // 'My first post'

```

## Documentation

Check the [Documentation](https://github.com/analogueorm/analogue/wiki) for more details.

## Features

- Framework agnostic
- Lazy loading
- Eager Loading
- Timestamps
- Soft Deletes
- Value Objects
- Polymorphic Relationships
- Dynamic Relationships
- Single table inheritance
- Cast entities to Array / Json
- Flexible event system
- Native multiple database connections support
- Extendable via custom database drivers / plugins


## Changelog 

#### Version 5.6
- Laravel 5.6 support
- Bring back ability to map DB columns that name are not equals to the name of the attribute.
- Add ability to map DB snake case columns to camel case properties on entities.

#### Version 5.5
- Laravel 5.5 support
- Pushed miminum requirements to PHP7
- Complete support of Plain PHP objects via reflection based hydration/dehydration
- Improved Lazy-loading proxies.
- New, more flexible Value Object implementation, that can now be defined as `embedsOne()`, `embedsMany()` relationships
- Embedded value object can now be stored as a mysql JSON field
- Analogue entities can now be instantiated using laravel's `IoC Container` or any PSR-11 compatible container. 
- Added [MongoDB](https://github.com/analogueorm/mongodb) driver.
- Package auto discovery (L5.5)

#### Version 5.4
- Illuminate 5.4 Compatibility.
- Add Ability to map DB columns that name are not equals to the name of the attribute.

#### Version 5.3
- Illuminate 5.3 Compatibility. 
- Now fully support Single Table Inheritance.

#### Version 5.1
- Illuminate 5.1 + 5.2 Compatibility. 

#### Version 5.0
- Analogue version now mirrors illuminate version. 

#### Version 2.1.3
- Mutator feature in base Entity class.
- Ability to add entities to a proxy collection without lazyloading it.

### Version 2.1

- Package is now framework agnostic.
- Now support any plain object that implements Mappable interface.
- Introducing a MappableTrait for quick implementation. 
- Queries can now be run directly on the mapper Object. 
- Store/Delete methods now accept a array and collections as argument.
- EntityMap are now autodected when in the same namespace as the entity.
- Base Entity class Supports hidden attributes.
- Many workflow related improvements.

### Version 2.0

- Laravel 5 Support.

## Documentation

Check the [wiki](https://github.com/analogueorm/analogue/wiki) for full documentation.

## Licence

This package is licensed under the [MIT License](http://opensource.org/licenses/MIT).
