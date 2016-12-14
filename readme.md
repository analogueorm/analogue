# Analogue ORM 
[![Build Status](https://travis-ci.org/analogueorm/analogue.svg?branch=5.3)](https://travis-ci.org/analogueorm/analogue.svg?branch=5.3)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/analogueorm/analogue/badges/quality-score.png?b=5.3)](https://scrutinizer-ci.com/g/analogueorm/analogue/?branch=5.3)
[![Latest Version](https://img.shields.io/github/release/analogueorm/analogue.svg?style=flat-square)](https://github.com/analogueorm/analogue/releases)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)

**Analogue** is a flexible, easy-to-use **Data Mapper ORM** for **PHP**. It provides a quick and intuitive way to query and persist custom domain objects into a SQL Database. 

The project started as a fork from **Eloquent** by *Taylor Otwell*, and evolved into a fully featured Data Mapper, that sits on top of the very robust **Laravel Database** component. That said, **Analogue** is able to peacefuly coexists with its cousin in a same application *(only limitation is you cannot have relationships between the two, which is a common moraly accepted behaviour in the same family...)*

Analogue can be used as a **standalone package**, or can be transparently integrated into **Laravel** or **Lumen** via a dedicated ServiceProvider. A [Silex](https://github.com/anthonysterling/silex-provider-analogue-orm) service provider is also available.

If you're already familiar with Eloquent, a lot of the syntax is similar, so you should be up and running in no time. In fact, you may probably **gain time** as Analogue leverage some heavy DB tasks as **synchronizing complex relationships**., letting you think in term of **objects** and **collections** instead.

```php
$files = $filesystem->files('/path/to/gallery');

$gallery = new Gallery('Trip to India');

foreach($files as $file)
{
    $photo = new Photo($file);
    $gallery->addPhoto($photo);
}

$mapper->store($gallery);

```

If you intend to build applications following the **DDD** approach, **Analogue** can be a great asset for you.

Check the [Documentation](https://github.com/analogueorm/analogue/wiki) for more details.

##Features

- Framework agnostic
- Lazy loading
- Eager Loading
- Timestamps
- Soft Deletes
- Value Objects
- Polymorphic Relationships
- Dynamic Relationships
- Cast entities to Array / Json
- Flexible event system
- Native multiple database connections support
- Extendable via Plugins

## Install :

```
composer require analogue/orm
```

See [Configuration](https://github.com/analogueorm/analogue/wiki/Installation) for more information.

##Changelog 

####Version 5.3
- Illuminate 5.3 Compatibility. 
- now fully support Single Table Inheritance

####Version 5.1
- Illuminate 5.1 + 5.2 Compatibility. 

####Version 5.0
- Analogue version now mirrors illuminate version. 

####Version 2.1.3
- Mutator feature in base Entity class
- Ability to add entities to a proxy collection without lazyloading it.

###Version 2.1

- Package is now framework agnostic.
- Now support any plain object that implements Mappable interface.
- Introducing a MappableTrait for quick implementation. 
- Queries can now be run directly on the mapper Object. 
- Store/Delete methods now accept a array and collections as argument.
- EntityMap are now autodected when in the same namespace as the entity.
- Base Entity class Supports hidden attributes
- Many workflow related improvements.

###Version 2.0

- Laravel 5 Support.

## Documentation

Check the [wiki](https://github.com/analogueorm/analogue/wiki) for full documentation.

## Licence

This package is licensed under the [MIT License](http://opensource.org/licenses/MIT).

